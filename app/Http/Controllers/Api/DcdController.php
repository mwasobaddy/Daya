<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Referral;
use App\Services\VentureShareService;
use App\Services\QRCodeService;
use App\Mail\ReferralBonusNotification;
use App\Mail\DcdTokenAllocationNotification;
use App\Mail\WalletCreated;
use App\Mail\DcdWelcome;
use App\Mail\AdminDcdRegistration;
use App\Rules\TurnstileToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mail;

class DcdController extends Controller
{
    protected $ventureShareService;
    protected $qrCodeService;

    public function __construct(VentureShareService $ventureShareService, QRCodeService $qrCodeService)
    {
        $this->ventureShareService = $ventureShareService;
        $this->qrCodeService = $qrCodeService;
    }

    public function create(Request $request)
    {
        try {
            \Log::info('DCD Create request received', $request->all());

            $request->validate([
            // Referral & Identification
            'referral_code' => 'nullable|string|exists:users,referral_code',
            'full_name' => 'required|string|max:255',
            'national_id' => 'required|string|unique:users,national_id',
            'dob' => 'required|date|before:today',
            'gender' => 'nullable|in:male,female,other',
            'email' => 'required|email|unique:users',
            'ward_id' => 'required|exists:wards,id',
            'business_address' => 'required|string',
            'phone' => 'required|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',

            // Business Information
            'business_name' => 'nullable|string',
            'business_types' => 'required|array|min:1',
            'business_types.*' => 'string',
            'other_business_type' => 'required_if:business_types.*,other|string|nullable',

            // Business Traffic & Hours
            'daily_foot_traffic' => 'required|in:1-10,11-50,51-100,101-500,500+',
            'operating_hours_start' => 'nullable|date_format:H:i',
            'operating_hours_end' => 'nullable|date_format:H:i',
            'operating_days' => 'nullable|array',
            'operating_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',

            // Campaign Preferences
            'campaign_types' => 'required|array|min:1',
            'campaign_types.*' => 'in:music,movies,games,mobile_apps,product_launch,surveys,events,education',

            // Music Preferences
            'music_genres' => 'required_if:campaign_types.*,music|array',
            'music_genres.*' => 'string',

            // Content Safety
            'safe_for_kids' => 'boolean',

            // Wallet Setup
            'wallet_type' => 'required|in:personal,business,both',
            'wallet_pin' => 'required|string|size:4|regex:/^[0-9]+$/',
            'confirm_pin' => 'required|string|same:wallet_pin',

            // Agreement
            'terms' => 'required|accepted',
            'turnstile_token' => ['required', new TurnstileToken],
        ]);

        \Log::info('Validation passed, proceeding with user creation');

        // Find the referrer (if referral code provided) - can be any role
        $referrer = null;
        if ($request->referral_code) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
            if ($referrer) {
                \Log::info('Referrer found', [
                    'referral_code' => $request->referral_code,
                    'referrer_id' => $referrer->id,
                    'referrer_name' => $referrer->name,
                    'referrer_role' => $referrer->role
                ]);
            } else {
                \Log::warning('Referral code provided but no referrer found', [
                    'referral_code' => $request->referral_code
                ]);
            }
        } else {
            \Log::info('No referral code provided');
        }

        // Get ward and populate location hierarchy
        $ward = \App\Models\Ward::with('subcounty.county.country')->find($request->ward_id);
        if (!$ward) {
            return response()->json(['message' => 'Invalid ward selected'], 422);
        }

        // Generate unique referral code for new DCD
        do {
            $referralCode = Str::upper(Str::random(8));
        } while (User::where('referral_code', $referralCode)->exists());

        // Create the user with comprehensive profile data
        $user = User::create([
            'name' => $request->full_name,
            'email' => $request->email,
            'password' => bcrypt($request->wallet_pin), // Use wallet PIN as initial password
            'role' => 'dcd',
            'national_id' => $request->national_id,
            'phone' => $request->phone,
            'country_id' => $ward->subcounty->county->country->id,
            'county_id' => $ward->subcounty->county->id,
            'subcounty_id' => $ward->subcounty->id,
            'ward_id' => $request->ward_id,
            'wallet_pin' => bcrypt($request->wallet_pin),
            'wallet_type' => $request->wallet_type,
            'wallet_status' => 'active',
            'referral_code' => $referralCode, // Generate referral code for this DCD
            'profile' => [
                // Personal Information
                'full_name' => $request->full_name,
                'date_of_birth' => $request->dob,
                'gender' => $request->gender,

                // Geographic Information
                'ward_id' => $request->ward_id,
                'business_address' => $request->business_address,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,

                // Business Information
                'business_name' => $request->business_name,
                'business_types' => $request->business_types,
                'other_business_type' => $request->other_business_type,

                // Business Traffic & Hours
                'daily_foot_traffic' => $request->daily_foot_traffic,
                'operating_hours_start' => $request->operating_hours_start,
                'operating_hours_end' => $request->operating_hours_end,
                'operating_days' => $request->operating_days,

                // Campaign Preferences
                'campaign_types' => $request->campaign_types,

                // Music Preferences
                'music_genres' => $request->music_genres,

                // Content Safety
                'safe_for_kids' => $request->safe_for_kids,

                // Terms Acceptance
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
            ],
        ]);

        \Log::info('User created successfully', ['user_id' => $user->id, 'email' => $user->email]);

        // Verify user was actually saved
        $savedUser = User::find($user->id);
        if (!$savedUser) {
            throw new \Exception('User was not saved to database');
        }
        \Log::info('User verified in database', ['user_id' => $savedUser->id]);

        // Generate QR code file and store filename in DB
        $qrFilename = $this->qrCodeService->generateDcdQr($user);

        // Update user with QR code filename
        $user->update(['qr_code' => $qrFilename]);

        // Create referral record if referrer exists
        if ($referrer) {
            $referralType = match($referrer->role) {
                'da' => 'da_to_dcd',
                'dcd' => 'dcd_to_dcd',
                default => 'da_to_dcd'  // fallback
            };
            
            $referral = Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'type' => $referralType,
            ]);

            // Allocate venture shares for the referral
            $this->ventureShareService->allocateSharesForReferral($referral);

            // Notify referrer of venture share update
            try {
                \Mail::to($referrer->email)->send(new ReferralBonusNotification($referrer, $this->ventureShareService));
            } catch (\Exception $e) {
                \Log::warning('Failed to send referral bonus notification to DA: ' . $e->getMessage());
            }
        }

        // Send welcome email
        \Log::info('Sending welcome email', [
            'user_id' => $user->id,
            'referrer_id' => $referrer ? $referrer->id : null,
            'referrer_name' => $referrer ? $referrer->name : 'None'
        ]);
        Mail::to($user->email)->send(new DcdWelcome($user, $referrer, $qrFilename));

    // Send wallet creation notification
    try {
        Mail::to($user->email)->send(new WalletCreated($user));
    } catch (\Exception $e) {
        \Log::warning('Failed to send wallet creation email to DCD: ' . $e->getMessage());
    }

        // Allocate initial DCD registration tokens (1000 DDS + 1000 DWS)
        try {
            $this->ventureShareService->allocateInitialDcdTokens($user);
            \Log::info('Initial DCD tokens allocated', [
                'user_id' => $user->id,
                'dds_tokens' => 1000,
                'dws_tokens' => 1000
            ]);

            // Send token allocation notification email
            Mail::to($user->email)->send(new DcdTokenAllocationNotification($user, $this->ventureShareService));
            \Log::info('DCD token allocation notification sent', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
        } catch (\Exception $e) {
            \Log::warning('Failed to allocate initial DCD tokens or send notification: ' . $e->getMessage());
        }

        // Send admin notification email to all admin users
        $adminUsers = User::where('role', 'admin')->get();
        if ($adminUsers->count() > 0) {
            \Log::info('Sending admin notifications', [
                'admin_count' => $adminUsers->count(),
                'referrer_info' => $referrer ? ['id' => $referrer->id, 'name' => $referrer->name, 'role' => $referrer->role] : null
            ]);
            foreach ($adminUsers as $admin) {
                Mail::to($admin->email)->send(new AdminDcdRegistration($user, $referrer));
            }
        }

        return response()->json([
            'message' => 'DCD registered successfully',
            'qr_code' => $qrFilename,
            'user' => $user
        ]);
        } catch (\Exception $e) {
            \Log::error('DCD registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
