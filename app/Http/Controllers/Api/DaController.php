<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Referral;
use App\Services\VentureShareService;
use App\Services\QRCodeService;
use App\Mail\ReferralBonusNotification;
use App\Mail\DaReferralCommissionNotification;
use App\Mail\DcdReferralBonusNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Mail;

class DaController extends Controller
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
            \Log::info('DA Create request received', $request->all());

            $request->validate([
                'referral_code' => 'nullable|string|regex:/^[A-Za-z0-9]{6,8}$/',
                'referrer_id' => 'nullable|exists:users,id',
                'full_name' => 'required|string|max:255',
                'national_id' => 'required|string|unique:users,national_id',
                'dob' => 'required|date|before:today',
                'gender' => 'nullable|in:male,female,other',
                'email' => 'required|email|unique:users',
                'ward_id' => 'required|exists:wards,id',
                'address' => 'required|string',
                'phone' => 'required|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'platforms' => 'required|array|min:1',
                'platforms.*' => 'string',
                'followers' => 'required|in:less_than_1k,1k_10k,10k_50k,50k_100k,100k_plus',
                'communication_channel' => 'required|in:whatsapp,telegram,email,phone',
                'wallet_type' => 'required|in:personal,business,both',
                'wallet_pin' => 'required|string|size:4|regex:/^[0-9]+$/',
                'confirm_pin' => 'required|string|same:wallet_pin',
                'terms' => 'required|accepted',
                'turnstile_token' => 'nullable|string',
            ]);

            \Log::info('Validation passed, proceeding with user creation');

            // Find the referrer (if referral code provided and valid, or referrer_id provided)
            $referrer = null;
            if ($request->referral_code && strlen($request->referral_code) >= 6) {
                $referrer = User::where('referral_code', strtoupper($request->referral_code))->first();
                if ($referrer) {
                    \Log::info('Referrer found by referral code', [
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
            } elseif ($request->referrer_id) {
                $referrer = User::find($request->referrer_id);
                if ($referrer) {
                    \Log::info('Referrer found by ID', [
                        'referrer_id' => $referrer->id,
                        'referrer_name' => $referrer->name,
                        'referrer_role' => $referrer->role
                    ]);
                } else {
                    \Log::warning('Referrer ID provided but no referrer found', [
                        'referrer_id' => $request->referrer_id
                    ]);
                }
            } else {
                \Log::info('No referral code or referrer ID provided', [
                    'referral_code' => $request->referral_code,
                    'referrer_id' => $request->referrer_id
                ]);
            }

            // Get ward and populate location hierarchy
            $ward = \App\Models\Ward::with('subcounty.county.country')->find($request->ward_id);
            if (!$ward) {
                return response()->json(['message' => 'Invalid ward selected'], 422);
            }

            // Generate unique referral code for new DA
            do {
                $referralCode = Str::upper(Str::random(6));
            } while (User::where('referral_code', $referralCode)->exists());

            // Create the user with comprehensive profile data
            $user = User::create([
                'name' => $request->full_name,
                'email' => $request->email,
                'password' => bcrypt($request->wallet_pin), // Use wallet PIN as initial password
                'role' => 'da',
                'national_id' => $request->national_id,
                'phone' => $request->phone,
                'country_id' => $ward->subcounty->county->country->id,
                'county_id' => $ward->subcounty->county->id,
                'subcounty_id' => $ward->subcounty->id,
                'ward_id' => $request->ward_id,
                'wallet_pin' => bcrypt($request->wallet_pin),
                'wallet_type' => $request->wallet_type,
                'wallet_status' => 'active',
                'referral_code' => $referralCode, // Generate 6-character referral code for this DA
                'profile' => [
                    // Personal Information
                    'full_name' => $request->full_name,
                    'date_of_birth' => $request->dob,
                    'gender' => $request->gender,

                    // Geographic Information
                    'country_id' => $request->country,
                    'county_id' => $request->county,
                    'subcounty_id' => $request->subcounty,
                    'ward_id' => $request->ward_id,
                    'address' => $request->address,
                    'latitude' => $request->latitude,
                    'longitude' => $request->longitude,

                    // Social Media Information
                    'platforms' => $request->platforms,
                    'followers' => $request->followers,
                    'communication_channel' => $request->communication_channel,

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

            // Generate QR code as PDF file and save filename
            // $qrFilename = $this->qrCodeService->generateDAReferralQRCode($user);

            // Update user with QR code filename
            // $user->update(['qr_code' => $qrFilename]);

            // Create referral record if referrer exists
            if ($referrer) {
                $referralType = match($referrer->role) {
                    'admin' => 'admin_to_da',
                    'da' => 'da_to_da',
                    'dcd' => 'dcd_to_da',
                    default => 'da_to_da'  // fallback for any other role
                };
                
                $referral = \App\Models\Referral::create([
                    'referrer_id' => $referrer->id,
                    'referred_id' => $user->id,
                    'type' => $referralType,
                ]);

                // Allocate venture shares for the referral
                $this->ventureShareService->allocateSharesForReferral($referral);

                // Notify referrer of venture share update based on their role
                if ($referrer->role === 'da') {
                    try {
                        \Mail::to($referrer->email)->send(new \App\Mail\ReferralBonusNotification($referrer, $this->ventureShareService));
                    } catch (\Exception $e) {
                        \Log::warning('Failed to send referral bonus notification to DA: ' . $e->getMessage());
                    }

                    // Send commission notification to DA referrer about 5% campaign budget earnings
                    try {
                        \Mail::to($referrer->email)->send(new \App\Mail\DaReferralCommissionNotification($referrer, $user));
                        \Log::info('DA referral commission notification sent', [
                            'referrer_id' => $referrer->id,
                            'new_da_id' => $user->id,
                            'referrer_email' => $referrer->email
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to send DA referral commission notification: ' . $e->getMessage());
                    }
                } elseif ($referrer->role === 'dcd') {
                    // Send DCD-specific referral bonus notification (1,000 + 1,000 tokens)
                    try {
                        \Mail::to($referrer->email)->send(new DcdReferralBonusNotification($referrer, $user, $this->ventureShareService));
                        \Log::info('DCD referral bonus notification sent', [
                            'referrer_id' => $referrer->id,
                            'new_da_id' => $user->id,
                            'referrer_email' => $referrer->email,
                            'tokens_awarded' => '1000 DDS + 1000 DWS'
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to send DCD referral bonus notification: ' . $e->getMessage());
                    }
                }
            }

            // Send welcome email
            \Log::info('Sending welcome email', [
                'user_id' => $user->id,
                'referrer_id' => $referrer ? $referrer->id : null,
                'referrer_name' => $referrer ? $referrer->name : 'None'
            ]);
            Mail::to($user->email)->send(new \App\Mail\DaWelcome($user, $referrer));

            // Send wallet creation notification
            try {
                Mail::to($user->email)->send(new \App\Mail\WalletCreated($user));
            } catch (\Exception $e) {
                \Log::warning('Failed to send wallet creation email to DA: ' . $e->getMessage());
            }

            // Send admin notification email to all admin users
            $adminUsers = User::where('role', 'admin')->get();
            if ($adminUsers->count() > 0) {
                \Log::info('Sending admin notifications', [
                    'admin_count' => $adminUsers->count(),
                    'referrer_info' => $referrer ? ['id' => $referrer->id, 'name' => $referrer->name, 'role' => $referrer->role] : null
                ]);
                foreach ($adminUsers as $admin) {
                    Mail::to($admin->email)->send(new \App\Mail\AdminDaRegistration($user, $referrer));
                }
            }

            return response()->json([
                'message' => 'DA registered successfully',
                // 'qr_code' => $qrFilename,
                'user' => $user
            ]);
        } catch (\Exception $e) {
            \Log::error('DA registration failed', [
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
