<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\User;
use App\Services\VentureShareService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Mail;

class AdminController extends Controller
{
    protected $ventureShareService;

    public function __construct(VentureShareService $ventureShareService)
    {
        $this->ventureShareService = $ventureShareService;
    }

    /**
     * Approve a pending campaign
     */
    public function approveCampaign(Request $request, $campaignId)
    {
        $request->validate([
            'admin_token' => 'required|string', // Simple token-based auth for demo
        ]);

        // Simple admin authentication (in production, use proper auth)
        if (!Hash::check($request->admin_token, hash('sha256', 'daya_admin_2024'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status !== 'pending') {
            return response()->json(['message' => 'Campaign is not pending'], 400);
        }

        $campaign->update(['status' => 'approved']);

        // Notify DCD that campaign is approved
        $dcd = User::find($campaign->dcd_id);
        $client = User::find($campaign->client_id);

        Mail::to($dcd->email)->send(new \App\Mail\CampaignApproved($campaign, $client));

        // Notify the DA who referred this DCD
        $referral = $dcd->referralsReceived()->where('type', 'da_to_dcd')->first();
        if ($referral && $referral->referrer) {
            $da = $referral->referrer;
            try {
                Mail::to($da->email)->send(new \App\Mail\DaCampaignNotification($da, $dcd, $campaign));
            } catch (\Exception $e) {
                \Log::warning('Failed to send DaCampaignNotification email to DA: ' . $e->getMessage());
            }
        }

        return response()->json(['message' => 'Campaign approved successfully']);
    }

    /**
     * Mark a campaign as completed and allocate venture shares
     */
    public function completeCampaign(Request $request, $campaignId)
    {
        $request->validate([
            'admin_token' => 'required|string',
        ]);

        // Simple admin authentication
        if (!Hash::check($request->admin_token, hash('sha256', 'daya_admin_2024'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->status !== 'approved') {
            return response()->json(['message' => 'Campaign must be approved first'], 400);
        }

        $campaign->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Allocate venture shares for campaign completion
        $dcd = User::find($campaign->dcd_id);
        $this->ventureShareService->allocateSharesForCampaignCompletion($dcd, $campaign->budget);

        // Notify client and DCD
        $client = User::find($campaign->client_id);
        Mail::to($client->email)->send(new \App\Mail\CampaignCompleted($campaign, $dcd));
        Mail::to($dcd->email)->send(new \App\Mail\CampaignCompleted($campaign, $client));

        return response()->json(['message' => 'Campaign completed successfully']);
    }

    /**
     * Get all campaigns for admin review
     */
    public function getCampaigns(Request $request)
    {
        $request->validate([
            'admin_token' => 'required|string',
        ]);

        // Simple admin authentication
        if (!Hash::check($request->admin_token, hash('sha256', 'daya_admin_2024'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $campaigns = Campaign::with(['client', 'dcd'])
                            ->orderBy('created_at', 'desc')
                            ->get();

        return response()->json($campaigns);
    }

    /**
     * Get venture shares summary for all users
     */
    public function getVentureSharesSummary(Request $request)
    {
        $request->validate([
            'admin_token' => 'required|string',
        ]);

        // Simple admin authentication
        if (!Hash::check($request->admin_token, hash('sha256', 'daya_admin_2024'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $users = User::whereIn('role', ['da', 'dcd', 'client'])
                    ->with('ventureShares')
                    ->get()
                    ->map(function ($user) {
                        $totalShares = $this->ventureShareService->getTotalShares($user);
                        return [
                            'id' => $user->id,
                            'name' => $user->name,
                            'email' => $user->email,
                            'role' => $user->role,
                            'kedds_total' => $totalShares['kedds'],
                            'kedws_total' => $totalShares['kedws'],
                            'referral_code' => $user->referral_code,
                        ];
                    });

        return response()->json($users);
    }

    /**
     * Validate a referral code
     */
    public function validateReferralCode(Request $request)
    {
        $request->validate([
            'referral_code' => 'required|string|min:6|max:8|regex:/^[A-Z0-9]{6,8}$/',
        ]);

        $referralCode = strtoupper($request->referral_code);

        // Check if referral code exists in users table (case-insensitive search)
        $user = User::whereRaw('UPPER(referral_code) = ?', [$referralCode])->first();

        if (!$user) {
            return response()->json([
                'valid' => false,
                'message' => 'Invalid referral code'
            ], 400);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Valid referral code',
            'referrer' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role
            ]
        ]);
    }

    /**
     * Get the admin's referral code
     */
    public function getAdminReferralCode()
    {
        $admin = User::where('role', 'admin')->first();

        if (!$admin || !$admin->referral_code) {
            return response()->json([
                'error' => 'Admin referral code not found'
            ], 404);
        }

        return response()->json([
            'referral_code' => $admin->referral_code,
            'admin_name' => $admin->name
        ]);
    }

    /**
     * Validate an email address for uniqueness
     */
    public function validateEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
        ]);

        $email = strtolower($request->email);

        // Check if email already exists in users table (case-insensitive search)
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if ($user) {
            return response()->json([
                'valid' => false,
                'message' => 'This email address is already registered'
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Email address is available'
        ]);
    }

    /**
     * Validate a national ID for uniqueness
     */
    public function validateNationalId(Request $request)
    {
        $request->validate([
            'national_id' => 'required|string|max:255|regex:/^\d+$/',
        ]);

        $nationalId = $request->national_id;

        // Check if national ID already exists in users table
        $user = User::where('national_id', $nationalId)->first();

        if ($user) {
            return response()->json([
                'valid' => false,
                'message' => 'This National ID is already registered'
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'National ID is available'
        ]);
    }

    /**
     * Validate a phone number for uniqueness
     */
    public function validatePhone(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20|regex:/^\+?[\d\s\-()]{10,}$/',
        ]);

        $phone = $request->phone;

        // Check if phone number already exists in users table
        $user = User::where('phone', $phone)->first();

        if ($user) {
            return response()->json([
                'valid' => false,
                'message' => 'This phone number is already registered'
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'message' => 'Phone number is available'
        ]);
    }
}
