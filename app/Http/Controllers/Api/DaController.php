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
                'full_name' => 'required|string|max:255',
                'national_id' => 'required|string|unique:users,national_id',
                'dob' => 'required|date|before:today',
                'gender' => 'nullable|in:male,female,other',
                'email' => 'required|email|unique:users',
                'country' => 'required|exists:countries,id',
                'county' => 'required|exists:counties,id',
                'subcounty' => 'required|exists:subcounties,id',
                'ward' => 'required|exists:wards,id',
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

            // Find the referrer (if referral code provided and valid)
            $referrer = null;
            if ($request->referral_code && strlen($request->referral_code) >= 6) {
                $referrer = User::where('referral_code', strtoupper($request->referral_code))->first();
            }

            // Create the user with comprehensive profile data
            $user = User::create([
                'name' => $request->full_name,
                'email' => $request->email,
                'password' => bcrypt($request->wallet_pin), // Use wallet PIN as initial password
                'role' => 'da',
                'national_id' => $request->national_id,
                'phone' => $request->phone,
                'wallet_pin' => bcrypt($request->wallet_pin),
                'wallet_type' => $request->wallet_type,
                'wallet_status' => 'active',
                'referral_code' => Str::upper(Str::random(6)), // Generate 6-character referral code for this DA
                'profile' => [
                    // Personal Information
                    'full_name' => $request->full_name,
                    'date_of_birth' => $request->dob,
                    'gender' => $request->gender,

                    // Geographic Information
                    'country_id' => $request->country,
                    'county_id' => $request->county,
                    'subcounty_id' => $request->subcounty,
                    'ward_id' => $request->ward,
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

            // Generate QR code
            $qrCodeFilename = $this->qrCodeService->generateDAReferralQRCode($user);
            $qrCodeUrl = $this->qrCodeService->getQRCodeUrl($qrCodeFilename);

            // Update user with QR code
            $user->update(['qr_code' => $qrCodeUrl]);

            // Create referral record if referrer exists
            if ($referrer) {
                $referral = \App\Models\Referral::create([
                    'referrer_id' => $referrer->id,
                    'referred_id' => $user->id,
                    'type' => 'admin_to_da', // or whatever type based on referrer role
                ]);

                // Allocate venture shares for the referral
                $this->ventureShareService->allocateSharesForReferral($referral);
            }

            // Send welcome email
            Mail::to($user->email)->send(new \App\Mail\DaWelcome($user, $referrer));

            // Send admin notification email to all admin users
            $adminUsers = User::where('role', 'admin')->get();
            if ($adminUsers->count() > 0) {
                foreach ($adminUsers as $admin) {
                    Mail::to($admin->email)->send(new \App\Mail\AdminDaRegistration($user, $referrer));
                }
            }

            return response()->json([
                'message' => 'DA registered successfully',
                'qr_code' => $qrCodeUrl,
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
