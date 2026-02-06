<?php

namespace App\Services;

use App\Mail\CampaignApproved;
use App\Mail\CampaignCompleted;
use App\Mail\DaCampaignNotification;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AdminService
{
    protected $ventureShareService;

    public function __construct(VentureShareService $ventureShareService)
    {
        $this->ventureShareService = $ventureShareService;
    }

    public function approveCampaign(Campaign $campaign): void
    {
        $campaign->update(['status' => 'approved']);

        $this->notifyCampaignApproval($campaign);
    }

    public function completeCampaign(Campaign $campaign): void
    {
        $campaign->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        // Allocate venture shares
        $dcd = User::find($campaign->dcd_id);
        $this->ventureShareService->allocateSharesForCampaignCompletion($dcd, $campaign->budget);

        $this->notifyCampaignCompletion($campaign);
    }

    protected function notifyCampaignApproval(Campaign $campaign): void
    {
        $dcd = User::find($campaign->dcd_id);
        $client = User::find($campaign->client_id);

        try {
            Mail::to($dcd->email)->send(new CampaignApproved($campaign, $client));
        } catch (\Exception $e) {
            Log::warning('Failed to send campaign approval email: '.$e->getMessage());
        }

        // Notify the DA who referred this DCD
        $referral = $dcd->referralsReceived()->where('type', 'da_to_dcd')->first();
        if ($referral && $referral->referrer) {
            $da = $referral->referrer;
            try {
                Mail::to($da->email)->send(new DaCampaignNotification($da, $dcd, $campaign));
            } catch (\Exception $e) {
                Log::warning('Failed to send DaCampaignNotification email: '.$e->getMessage());
            }
        }
    }

    protected function notifyCampaignCompletion(Campaign $campaign): void
    {
        $client = User::find($campaign->client_id);
        $dcd = User::find($campaign->dcd_id);

        try {
            Mail::to($client->email)->send(new CampaignCompleted($campaign, $dcd));
            Mail::to($dcd->email)->send(new CampaignCompleted($campaign, $client));
        } catch (\Exception $e) {
            Log::warning('Failed to send campaign completion emails: '.$e->getMessage());
        }
    }

    public function getCampaigns()
    {
        return Campaign::with(['client', 'dcd'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getVentureSharesSummary()
    {
        return User::whereIn('role', ['da', 'dcd', 'client'])
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
    }

    public function validateReferralCode(string $referralCode): array
    {
        $user = User::whereRaw('UPPER(referral_code) = ?', [strtoupper($referralCode)])->first();

        if (! $user) {
            return [
                'valid' => false,
                'message' => 'Invalid referral code',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Valid referral code',
            'referrer' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
            ],
        ];
    }

    public function getAdminReferralCode(): ?array
    {
        $admin = User::where('role', 'admin')->first();

        if (! $admin || ! $admin->referral_code) {
            return null;
        }

        return [
            'referral_code' => $admin->referral_code,
            'admin_name' => $admin->name,
        ];
    }

    public function validateEmail(string $email): array
    {
        $user = User::whereRaw('LOWER(email) = ?', [strtolower($email)])->first();

        if ($user) {
            return [
                'valid' => false,
                'message' => 'This email address is already registered',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Email address is available',
        ];
    }

    public function validateNationalId(string $nationalId): array
    {
        $user = User::where('national_id', $nationalId)->first();

        if ($user) {
            return [
                'valid' => false,
                'message' => 'This National ID is already registered',
            ];
        }

        return [
            'valid' => true,
            'message' => 'National ID is available',
        ];
    }

    public function validatePhone(string $phone): array
    {
        $user = User::where('phone', $phone)->first();

        if ($user) {
            return [
                'valid' => false,
                'message' => 'This phone number is already registered',
            ];
        }

        return [
            'valid' => true,
            'message' => 'Phone number is available',
        ];
    }
}
