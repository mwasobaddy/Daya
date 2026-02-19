<?php

namespace App\Services;

use App\Models\Referral;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mail;

class DaCreationService
{
    protected $ventureShareService;

    public function __construct(VentureShareService $ventureShareService)
    {
        $this->ventureShareService = $ventureShareService;
    }

    public function createDa(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Find referrer
            $referrer = $this->findReferrer($data);

            // Get ward and populate location hierarchy
            $ward = \App\Models\Ward::with('subcounty.county.country')->find($data['ward_id']);
            if (! $ward) {
                throw new \Exception('Invalid ward selected');
            }

            // Generate unique referral code
            $referralCode = $this->generateUniqueReferralCode();

            // Create the user
            $user = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => bcrypt($data['wallet_pin']),
                'role' => 'da',
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
                    'country_id' => $ward->subcounty->county->country->id,
                    'county_id' => $ward->subcounty->county->id,
                    'subcounty_id' => $ward->subcounty->id,
                    'ward_id' => $data['ward_id'],
                    'address' => $data['address'],
                    'latitude' => $data['latitude'] ?? null,
                    'longitude' => $data['longitude'] ?? null,
                    'platforms' => $data['platforms'],
                    'followers' => $data['followers'],
                    'communication_channel' => $data['communication_channel'],
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                ],
            ]);

            // Create referral record if referrer exists
            if ($referrer) {
                $referralType = match ($referrer->role) {
                    'admin' => 'admin_to_da',
                    'da' => 'da_to_da',
                    'dcd' => 'dcd_to_da',
                    default => 'da_to_da'
                };

                $referral = Referral::create([
                    'referrer_id' => $referrer->id,
                    'referred_id' => $user->id,
                    'type' => $referralType,
                ]);

                // Allocate venture shares
                $this->ventureShareService->allocateSharesForReferral($referral);

                // Send notifications
                $this->sendReferralNotifications($referrer, $user);
            }

            // Send welcome email
            Mail::to($user->email)->send(new \App\Mail\DaWelcome($user));

            // Notify admins
            $this->notifyAdminsOfRegistration($user, $referrer);

            return $user;
        });
    }

    protected function findReferrer(array $data): ?User
    {
        if (! empty($data['referral_code'])) {
            return User::where('referral_code', strtoupper($data['referral_code']))->first();
        } elseif (! empty($data['referrer_id'])) {
            return User::find($data['referrer_id']);
        }

        return null;
    }

    protected function generateUniqueReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    protected function sendReferralNotifications(User $referrer, User $user): void
    {
        if ($referrer->role === 'da') {
            try {
                Mail::to($referrer->email)->send(new \App\Mail\ReferralBonusNotification($referrer, $this->ventureShareService));
                Mail::to($referrer->email)->send(new \App\Mail\DaReferralCommissionNotification($referrer, $user));
            } catch (\Exception $e) {
                \Log::warning('Failed to send referral notifications: '.$e->getMessage());
            }
        }
    }

    protected function notifyAdminsOfRegistration(User $user, ?User $referrer = null): void
    {
        $adminUsers = User::where('role', 'admin')->get();
        if ($adminUsers->isEmpty()) {
            \Log::warning('No admin users found to notify about DA registration');

            return;
        }

        foreach ($adminUsers as $admin) {
            try {
                Mail::to($admin->email)->send(new \App\Mail\AdminDaRegistration($user, $referrer));
            } catch (\Exception $e) {
                \Log::error('Failed to send admin notification to '.$admin->email.': '.$e->getMessage());
            }
        }

        \Log::info('Notified '.$adminUsers->count().' admin users about DA registration', [
            'da_id' => $user->id,
            'da_name' => $user->name,
            'admin_emails' => $adminUsers->pluck('email')->toArray(),
        ]);
    }
}
