<?php

namespace App\Services;

use App\Models\User;
use App\Models\VentureShare;
use App\Models\Referral;

class VentureShareService
{
    /**
     * Allocate venture shares when a DA refers a DCD
     */
    public function allocateSharesForReferral(Referral $referral): void
    {
        // Allocate shares to the DA who made the referral
        $this->allocateShares(
            $referral->referrer,
            100, // 100 KeDDS tokens for referring a DCD
            'KeDDS',
            'Referral bonus for registering DCD: ' . $referral->referred->name
        );

        // Allocate shares to the new DCD
        $this->allocateShares(
            $referral->referred,
            50, // 50 KeDDS tokens for joining as DCD
            'KeDDS',
            'Welcome bonus for joining Daya'
        );
    }

    /**
     * Allocate venture shares when a campaign is completed
     */
    public function allocateSharesForCampaignCompletion(User $dcd, float $campaignBudget): void
    {
        // DCD gets 20% of campaign budget as KeDDS tokens
        $dcdShares = $campaignBudget * 0.20;

        $this->allocateShares(
            $dcd,
            $dcdShares,
            'KeDDS',
            'Campaign completion bonus'
        );

        // Check if DCD was referred by a DA and allocate commission
        $referral = Referral::where('referred_id', $dcd->id)
                          ->where('type', 'da_to_dcd')
                          ->first();

        if ($referral) {
            // DA gets 10% commission of DCD's earnings
            $daCommission = $dcdShares * 0.10;

            $this->allocateShares(
                $referral->referrer,
                $daCommission,
                'KeDDS',
                'Commission from DCD campaign completion: ' . $dcd->name
            );
        }
    }

    /**
     * Allocate venture shares when a client submits a campaign
     */
    public function allocateSharesForCampaignSubmission(User $client, float $campaignBudget): void
    {
        // Client gets 5 KeDWS tokens for submitting a campaign
        $this->allocateShares(
            $client,
            5,
            'KeDWS',
            'Campaign submission bonus'
        );
    }

    /**
     * Generic method to allocate venture shares
     */
    private function allocateShares(User $user, float $amount, string $type, string $reason): void
    {
        VentureShare::create([
            'user_id' => $user->id,
            'kedds_amount' => $type === 'KeDDS' ? $amount : 0,
            'kedws_amount' => $type === 'KeDWS' ? $amount : 0,
            'reason' => $reason,
            'allocated_at' => now(),
        ]);
    }

    /**
     * Get total venture shares for a user
     */
    public function getTotalShares(User $user): array
    {
        $shares = VentureShare::where('user_id', $user->id)->get();

        return [
            'kedds' => $shares->sum('kedds_amount'),
            'kedws' => $shares->sum('kedws_amount'),
        ];
    }

    /**
     * Get venture share history for a user
     */
    public function getShareHistory(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return VentureShare::where('user_id', $user->id)
                          ->orderBy('allocated_at', 'desc')
                          ->get();
    }
}