<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Referral;
use App\Services\VentureShareService;
use App\Services\QRCodeService;
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
            'daily_foot_traffic' => 'required|in:less_than_50,50_200,200_plus',
            'operating_hours_start' => 'nullable|date_format:H:i',
            'operating_hours_end' => 'nullable|date_format:H:i',
            'operating_days' => 'nullable|array',
            'operating_days.*' => 'in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',

            // Campaign Preferences
            'campaign_types' => 'required|array|min:1',
            'campaign_types.*' => 'in:music,movies,games,mobile_apps,product_launch,product_activation,events,education',

            // Music Preferences
            'music_genres' => 'required_if:campaign_types.*,music|array',
            'music_genres.*' => 'string',
            'other_music_genre' => 'required_if:music_genres.*,other|string|nullable',

            // Content Safety
            'safe_for_kids' => 'boolean',

            // Wallet Setup
            'wallet_type' => 'required|in:personal,business,both',
            'wallet_pin' => 'required|string|size:4|regex:/^[0-9]+$/',
            'confirm_pin' => 'required|string|same:wallet_pin',

            // Agreement
            'terms' => 'required|accepted',

            // Security
            'turnstile_token' => 'required|string',
        ]);

        // Find the referrer (if referral code provided) - can be any role
        $referrer = null;
        if ($request->referral_code) {
            $referrer = User::where('referral_code', $request->referral_code)->first();
        }

        // Create the user with comprehensive profile data
        $user = User::create([
            'name' => $request->full_name,
            'email' => $request->email,
            'password' => bcrypt($request->wallet_pin), // Use wallet PIN as initial password
            'role' => 'dcd',
            'national_id' => $request->national_id,
            'phone' => $request->phone,
            'ward_id' => $request->ward_id,
            'wallet_pin' => bcrypt($request->wallet_pin),
            'wallet_type' => $request->wallet_type,
            'wallet_status' => 'active',
            'referral_code' => Str::upper(Str::random(8)), // Generate referral code for this DCD
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
                'other_music_genre' => $request->other_music_genre,

                // Content Safety
                'safe_for_kids' => $request->safe_for_kids,

                // Terms Acceptance
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
            ],
        ]);

        // Generate QR code
        $qrCodeFilename = $this->qrCodeService->generateDCDQRCode($user);
        $qrCodeUrl = $this->qrCodeService->getQRCodeUrl($qrCodeFilename);

        // Update user with QR code
        $user->update(['qr_code' => $qrCodeUrl]);

        // Create referral record if referrer exists
        if ($referrer) {
            $referral = Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'type' => 'da_to_dcd',
            ]);

            // Allocate venture shares for the referral
            $this->ventureShareService->allocateSharesForReferral($referral);
        }

        // Send welcome email
        Mail::to($user->email)->send(new \App\Mail\DcdWelcome($user, $referrer));

        return response()->json([
            'message' => 'DCD registered successfully',
            'qr_code' => $qrCodeUrl,
            'user' => $user
        ]);
    }
}
