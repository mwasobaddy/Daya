<?php

namespace App\Services;

use App\Mail\AdminDcdRegistration;
use App\Mail\DcdTokenAllocationNotification;
use App\Mail\DcdWelcome;
use App\Mail\ReferralBonusNotification;
use App\Mail\WalletCreated;
use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class DcdCreationService
{
    protected $ventureShareService;

    protected $qrCodeService;

    public function __construct(VentureShareService $ventureShareService, QRCodeService $qrCodeService)
    {
        $this->ventureShareService = $ventureShareService;
        $this->qrCodeService = $qrCodeService;
    }

    public function createDcd(array $data): array
    {
        Log::info('DCD Create request received', $data);

        try {
            Log::info('Validation passed, proceeding with user creation');

            $referrer = $this->findReferrer($data);

            $ward = $this->getWard($data['ward_id']);

            $referralCode = $this->generateReferralCode();

            $user = $this->createUser($data, $ward, $referralCode);

            $qrFilename = $this->generateQrCode($user);

            $this->processReferral($referrer, $user);

            $this->sendWelcomeEmails($user, $referrer, $qrFilename);

            $this->allocateInitialTokens($user);

            $this->sendAdminNotifications($user, $referrer);

            Log::info('DCD registered successfully', ['user_id' => $user->id]);

            return [
                'message' => 'DCD registered successfully',
                'qr_code' => $qrFilename,
                'user' => $user,
            ];

        } catch (\Exception $e) {
            Log::error('DCD registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $data,
            ]);

            throw $e;
        }
    }

    protected function findReferrer(array $data): ?User
    {
        if ($data['referral_code']) {
            $referrer = User::where('referral_code', $data['referral_code'])->first();
            if ($referrer) {
                Log::info('Referrer found', [
                    'referral_code' => $data['referral_code'],
                    'referrer_id' => $referrer->id,
                    'referrer_name' => $referrer->name,
                    'referrer_role' => $referrer->role,
                ]);
            } else {
                Log::warning('Referral code provided but no referrer found', [
                    'referral_code' => $data['referral_code'],
                ]);
            }

            return $referrer;
        }
        Log::info('No referral code provided');

        return null;
    }

    protected function getWard(int $wardId): \App\Models\Ward
    {
        $ward = \App\Models\Ward::with('subcounty.county.country')->find($wardId);
        if (! $ward) {
            throw new \InvalidArgumentException('Invalid ward selected');
        }

        return $ward;
    }

    protected function generateReferralCode(): string
    {
        do {
            $referralCode = Str::upper(Str::random(8));
        } while (User::where('referral_code', $referralCode)->exists());

        return $referralCode;
    }

    protected function createUser(array $data, \App\Models\Ward $ward, string $referralCode): User
    {
        $user = User::create([
            'name' => $data['full_name'],
            'email' => $data['email'],
            'password' => bcrypt($data['wallet_pin']),
            'role' => 'dcd',
            'national_id' => $data['national_id'],
            'phone' => $data['phone'],
            'country_id' => $ward->subcounty->county->country->id,
            'county_id' => $ward->subcounty->county->id,
            'subcounty_id' => $ward->subcounty->id,
            'ward_id' => $data['ward_id'],
            'wallet_pin' => bcrypt($data['wallet_pin']),
            'wallet_type' => $data['wallet_type'],
            'wallet_status' => 'active',
            'referral_code' => $referralCode,
            'profile' => [
                'full_name' => $data['full_name'],
                'date_of_birth' => $data['dob'],
                'gender' => $data['gender'],
                'ward_id' => $data['ward_id'],
                'business_address' => $data['business_address'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'business_name' => $data['business_name'],
                'business_types' => $data['business_types'],
                'other_business_type' => $data['other_business_type'],
                'daily_foot_traffic' => $data['daily_foot_traffic'],
                'operating_hours_start' => $data['operating_hours_start'],
                'operating_hours_end' => $data['operating_hours_end'],
                'operating_days' => $data['operating_days'],
                'campaign_types' => $data['campaign_types'],
                'music_genres' => $data['music_genres'],
                'safe_for_kids' => $data['safe_for_kids'],
                'terms_accepted' => true,
                'terms_accepted_at' => now(),
            ],
        ]);

        Log::info('User created successfully', ['user_id' => $user->id, 'email' => $user->email]);

        $savedUser = User::find($user->id);
        if (! $savedUser) {
            throw new \Exception('User was not saved to database');
        }
        Log::info('User verified in database', ['user_id' => $savedUser->id]);

        return $user;
    }

    protected function generateQrCode(User $user): string
    {
        return $this->qrCodeService->generateDcdQr($user);
    }

    protected function processReferral(?User $referrer, User $user): void
    {
        if ($referrer) {
            $referralType = match ($referrer->role) {
                'da' => 'da_to_dcd',
                'dcd' => 'dcd_to_dcd',
                default => 'da_to_dcd'
            };

            $referral = Referral::create([
                'referrer_id' => $referrer->id,
                'referred_id' => $user->id,
                'type' => $referralType,
            ]);

            $this->ventureShareService->allocateSharesForReferral($referral);

            try {
                Mail::to($referrer->email)->send(new ReferralBonusNotification($referrer, $this->ventureShareService));
            } catch (\Exception $e) {
                Log::warning('Failed to send referral bonus notification to DA: '.$e->getMessage());
            }
        }
    }

    protected function sendWelcomeEmails(User $user, ?User $referrer, string $qrFilename): void
    {
        Log::info('Sending welcome email', [
            'user_id' => $user->id,
            'referrer_id' => $referrer ? $referrer->id : null,
            'referrer_name' => $referrer ? $referrer->name : 'None',
        ]);
        Mail::to($user->email)->send(new DcdWelcome($user, $referrer, $qrFilename));

        Log::info('Sending wallet creation notification', ['user_id' => $user->id, 'email' => $user->email]);
        Mail::to($user->email)->send(new WalletCreated($user));
    }

    protected function allocateInitialTokens(User $user): void
    {
        $this->ventureShareService->allocateInitialDcdTokens($user);
        Log::info('Initial DCD tokens allocated', [
            'user_id' => $user->id,
            'dds_tokens' => 1000,
            'dws_tokens' => 1000,
        ]);

        Log::info('Sending DCD token allocation notification', ['user_id' => $user->id, 'email' => $user->email]);
        Mail::to($user->email)->send(new DcdTokenAllocationNotification($user, $this->ventureShareService));
    }

    protected function sendAdminNotifications(User $user, ?User $referrer): void
    {
        $adminUsers = User::where('role', 'admin')->get();
        if ($adminUsers->count() > 0) {
            Log::info('Sending admin notifications', [
                'admin_count' => $adminUsers->count(),
                'referrer_info' => $referrer ? ['id' => $referrer->id, 'name' => $referrer->name, 'role' => $referrer->role] : null,
            ]);
            foreach ($adminUsers as $admin) {
                Mail::to($admin->email)->send(new AdminDcdRegistration($user, $referrer));
            }
        }
    }
}
