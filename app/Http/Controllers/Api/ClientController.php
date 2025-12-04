<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Country;
use App\Services\VentureShareService;
use App\Services\QRCodeService;
use App\Services\CampaignMatchingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mail;

class ClientController extends Controller
{
    protected $ventureShareService;
    protected $qrCodeService;
    protected $campaignMatchingService;

    public function __construct(VentureShareService $ventureShareService, QRCodeService $qrCodeService, CampaignMatchingService $campaignMatchingService)
    {
        $this->ventureShareService = $ventureShareService;
        $this->qrCodeService = $qrCodeService;
        $this->campaignMatchingService = $campaignMatchingService;
    }
    public function submitCampaign(Request $request)
    {
        \Log::info('Campaign submission started', ['email' => $request->email, 'campaign_title' => $request->campaign_title]);
        
        try {
            \Log::info('Campaign submission validation started', [
                'content_safety_preferences' => $request->content_safety_preferences,
                'has_content_safety_preferences' => !empty($request->content_safety_preferences)
            ]);
            
            $request->validate([
                // Account Information
                'account_type' => 'required|in:startup,artist,label,ngo,agency,business',
                'business_name' => 'required|string|max:255',
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'phone' => 'required|string|max:20',

                'country' => 'required|string|max:10',
                'referral_code' => 'nullable|string|max:50',
                'dcd_id' => 'nullable|integer|exists:users,id', // For QR code scans

                // Campaign Information
                'campaign_title' => 'required|string|max:255',
                'digital_product_link' => 'required|url|max:500',
                'explainer_video_url' => 'nullable|url|max:500',
                'campaign_objective' => 'required|in:music_promotion,app_downloads,brand_awareness,product_launch,event_promotion,social_cause',
                'budget' => 'required|numeric|min:50',
                'description' => 'required|string|max:2000',

                // Targeting & Budget
                'content_safety_preferences' => 'required|array|min:1',
                'content_safety_preferences.*' => 'string|in:kids,teen,adult,no_restrictions',
                'target_country' => 'required|string|max:10',
                'target_county' => 'nullable|string|max:50',
                'target_subcounty' => 'nullable|string|max:50',
                'target_ward' => 'nullable|string|max:50',
                'business_types' => 'required|array|min:1',
                'business_types.*' => 'string|max:50',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',

                // Music genres for labels/artists
                'music_genres' => 'required_if:account_type,artist,label|array',
                'music_genres.*' => 'string|max:50',
                'other_music_genre' => 'nullable|string|max:100',

                // Additional
                'target_audience' => 'nullable|string|max:1000',
                'objectives' => 'nullable|string|max:500',
            ]);

            \Log::info('Campaign submission validation passed', [
                'content_safety_preferences' => $request->content_safety_preferences,
                'content_safety_preferences_count' => count($request->content_safety_preferences ?? [])
            ]);

            // For now, we'll create campaigns without DCD assignment (can be assigned later by admin)
            // In a future version, this could be enhanced to support DCD QR code scanning

            // Enhanced user validation logic
            $existingUser = User::where('email', $request->email)->first();
            $client = null;

            if ($existingUser) {
                // Check if existing user details match
                $phoneMatches = $existingUser->phone === $request->phone;

                if (!$phoneMatches) {
                    // Details don't match - return error
                    return response()->json([
                        'message' => 'The email address you entered is already registered with different account details. Please verify your information and try again.',
                        'error' => 'account_mismatch'
                    ], 422);
                }

                // Details match - use existing user and update info
                $client = $existingUser;
                $client->update([
                    'name' => $request->name,
                    'phone' => $request->phone,
                    'country' => $request->country,
                    'business_name' => $request->business_name,
                    'account_type' => $request->account_type,
                    'referral_code' => $request->referral_code,
                    'country_id' => $request->target_country ? \App\Models\Country::where('code', strtoupper($request->target_country))->first()?->id : null,
                    'county_id' => $request->target_county,
                    'subcounty_id' => $request->target_subcounty,
                    'ward_id' => $request->target_ward, // Update ward_id (nullable)
                ]);
            } else {
                // Generate unique referral code for new client
                $referralCode = null;
                if ($request->referral_code) {
                    // Use provided code if it's unique
                    $existingCode = User::where('referral_code', strtoupper($request->referral_code))->first();
                    if (!$existingCode) {
                        $referralCode = strtoupper($request->referral_code);
                    }
                }
                
                // If no valid code provided, generate a unique one
                if (!$referralCode) {
                    do {
                        $referralCode = Str::upper(Str::random(8));
                    } while (User::where('referral_code', $referralCode)->exists());
                }

                // Create new client user
                $client = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'role' => 'client',
                    'phone' => $request->phone,
                    'country' => $request->country,
                    'business_name' => $request->business_name,
                    'account_type' => $request->account_type,
                    'referral_code' => $referralCode,
                    'country_id' => $request->target_country ? \App\Models\Country::where('code', strtoupper($request->target_country))->first()?->id : null,
                    'county_id' => $request->target_county,
                    'subcounty_id' => $request->target_subcounty,
                    'ward_id' => $request->target_ward, // Convert target_ward to ward_id (nullable)
                    'password' => bcrypt('temporary_password_' . time()), // Temporary password for client accounts
                ]);
            }

            // Convert content safety preferences to single value for storage
            $contentSafety = 'family_friendly'; // default
            if (in_array('adult', $request->content_safety_preferences) || in_array('no_restrictions', $request->content_safety_preferences)) {
                $contentSafety = 'mature_audience';
            }

            // Determine DCD assignment - either from QR scan or admin assignment later
            $dcdId = null;
            if ($request->dcd_id) {
                // Verify the DCD exists and is active
                $dcd = User::where('id', $request->dcd_id)->where('role', 'dcd')->first();
                if ($dcd) {
                    $dcdId = $dcd->id;
                    \Log::info('Campaign assigned to DCD from QR scan', ['dcd_id' => $dcdId, 'dcd_name' => $dcd->name]);
                }
            }

            // Create campaign with enhanced details
            // Validate other_music_genre if 'Other' selected in music_genres
            if (($request->account_type === 'label' || $request->account_type === 'artist') && is_array($request->music_genres ?? null)) {
                if (in_array('Other', $request->music_genres) && empty($request->other_music_genre)) {
                    return response()->json([
                        'message' => 'Please specify other music genre',
                        'errors' => ['other_music_genre' => ['Please specify other music genre when Other is selected']]
                    ], 422);
                }
            }
            $campaign = Campaign::create([
                'client_id' => $client->id,
                'dcd_id' => $dcdId, // From QR scan or null for admin assignment
                'title' => $request->campaign_title,
                'description' => $request->description,
                'budget' => $request->budget,
                'county' => $request->target_county ?? 'Not specified',
                'status' => 'submitted',
                'campaign_objective' => $request->campaign_objective,
                'digital_product_link' => $request->digital_product_link,
                'explainer_video_url' => $request->explainer_video_url,
                'target_audience' => $request->target_audience ?? 'General audience',
                'duration' => $request->start_date . ' to ' . $request->end_date,
                'objectives' => $request->objectives ?? 'Campaign objectives as described',
                'metadata' => [
                    'digital_product_link' => $request->digital_product_link,
                    'explainer_video_url' => $request->explainer_video_url,
                    'campaign_objective' => $request->campaign_objective,
                    'content_safety' => $contentSafety,
                    'content_safety_preferences' => $request->content_safety_preferences,
                    'target_country' => $request->target_country,
                    'target_county' => $request->target_county,
                    'target_subcounty' => $request->target_subcounty,
                    'target_ward' => $request->target_ward,
                    'business_types' => $request->business_types,
                    'music_genres' => $request->music_genres ?? [],
                    'other_music_genre' => $request->other_music_genre ?? null,
                    'start_date' => $request->start_date,
                    'end_date' => $request->end_date,
                    'account_type' => $request->account_type,
                    'business_name' => $request->business_name,
                    'phone' => $request->phone,
                    'country' => $request->country,
                    'referral_code' => $request->referral_code,
                ],
            ]);

            // Allocate venture shares for campaign submission
            try {
                $this->ventureShareService->allocateSharesForCampaignSubmission($client, $request->budget);
            } catch (\Exception $e) {
                \Log::warning('Failed to allocate venture shares: ' . $e->getMessage());
            }

            // Send confirmation email to client
            try {
                \Mail::to($client->email)->send(new \App\Mail\CampaignConfirmation($campaign, $campaign->dcd));
            } catch (\Exception $e) {
                \Log::warning('Failed to send confirmation email: ' . $e->getMessage());
            }

            // Send notification to all admin users for approval
            try {
                $adminActionService = app(\App\Services\AdminActionService::class);
                $adminActionService->notifyAllAdminsOfPendingCampaign($campaign);
            } catch (\Exception $e) {
                \Log::warning('Failed to notify admins: ' . $e->getMessage());
            }

            // Send admin notification email to all admin users about new campaign submission
            $adminUsers = User::where('role', 'admin')->get();
            if ($adminUsers->count() > 0) {
                \Log::info('Sending admin notifications for campaign submission', [
                    'admin_count' => $adminUsers->count(),
                    'campaign_id' => $campaign->id,
                    'client_id' => $client->id,
                    'referrer_info' => $request->referral_code ? ['referral_code' => $request->referral_code] : null
                ]);
                foreach ($adminUsers as $admin) {
                    try {
                        \Mail::to($admin->email)->send(new \App\Mail\AdminCampaignSubmission($campaign, $client, null));
                    } catch (\Exception $e) {
                        \Log::warning('Failed to send admin notification email: ' . $e->getMessage());
                    }
                }
            }

            \Log::info('Campaign submitted successfully', [
                'campaign_id' => $campaign->id,
                'client_id' => $client->id,
                'email' => $client->email
            ]);

            return response()->json([
                'message' => 'Campaign submitted successfully! Your campaign is now pending review. We will contact you soon.',
                'campaign_id' => $campaign->id,
                'client_id' => $client->id,
                'status' => 'success'
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Campaign submission failed: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Something went wrong while submitting your campaign. Please try again or contact support.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}
