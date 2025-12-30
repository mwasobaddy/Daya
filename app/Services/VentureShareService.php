<?php

namespace App\Services;

use App\Models\User;
use App\Models\VentureShare;
use App\Models\Referral;

class VentureShareService
{
    /**
     * Allocate venture shares based on referral type and new reward structure
     */
    public function allocateSharesForReferral(Referral $referral): void
    {
        $referrer = $referral->referrer;
        $referred = $referral->referred;
        
        // Get country-specific token prefixes
        $referrerTokens = $this->getTokenNamesForUser($referrer);
        $referredTokens = $this->getTokenNamesForUser($referred);
        
        switch ($referral->type) {
            case 'da_to_da':
                // DA to DA Referral: 200KeDWS + 200KeDDS to referring DA
                $this->allocateShares(
                    $referrer,
                    200,
                    $referrerTokens['dws'],
                    'DA referral bonus for registering DA: ' . $referred->name
                );
                $this->allocateShares(
                    $referrer,
                    200,
                    $referrerTokens['dds'],
                    'DA referral bonus for registering DA: ' . $referred->name
                );
                break;
                
            case 'da_to_dcd':
                // DA to DCD Referral: 500KeDWS + 500KeDDS to referring DA
                $this->allocateShares(
                    $referrer,
                    500,
                    $referrerTokens['dws'],
                    'DCD referral bonus for registering DCD: ' . $referred->name
                );
                $this->allocateShares(
                    $referrer,
                    500,
                    $referrerTokens['dds'],
                    'DCD referral bonus for registering DCD: ' . $referred->name
                );
                
                // DCD Reward: 1000KeDWS + 1000KeDDS to new DCD
                $this->allocateShares(
                    $referred,
                    1000,
                    $referredTokens['dws'],
                    'Welcome bonus for joining Daya as DCD'
                );
                $this->allocateShares(
                    $referred,
                    1000,
                    $referredTokens['dds'],
                    'Welcome bonus for joining Daya as DCD'
                );
                break;
                
            case 'dcd_to_da':
                // DCD to DA Referral: 1,000KeDWS + 1,000KeDDS to referring DCD
                $this->allocateShares(
                    $referrer,
                    1000,
                    $referrerTokens['dws'],
                    'DA referral bonus for registering DA: ' . $referred->name
                );
                $this->allocateShares(
                    $referrer,
                    1000,
                    $referrerTokens['dds'],
                    'DA referral bonus for registering DA: ' . $referred->name
                );
                break;
                
            case 'admin_to_da':
                // Admin to DA referral: 200 DDS + 200 DWS to referring admin
                $this->allocateShares(
                    $referrer,
                    200,
                    $referrerTokens['dds'],
                    'Admin referral bonus for registering DA: ' . $referred->name
                );
                $this->allocateShares(
                    $referrer,
                    200,
                    $referrerTokens['dws'],
                    'Admin referral bonus for registering DA: ' . $referred->name
                );
                break;
        }
    }

    /**
     * Allocate initial registration tokens to new DCD users
     */
    public function allocateInitialDcdTokens(User $dcd): void
    {
        $dcdTokens = $this->getTokenNamesForUser($dcd);

        // Allocate 1000 DDS tokens
        $this->allocateShares(
            $dcd,
            1000,
            $dcdTokens['dds'],
            'Initial registration bonus for joining Daya as DCD'
        );

        // Allocate 1000 DWS tokens  
        $this->allocateShares(
            $dcd,
            1000,
            $dcdTokens['dws'],
            'Initial registration bonus for joining Daya as DCD'
        );
    }

    /**
     * Allocate venture shares when a campaign is completed
     */
    public function allocateSharesForCampaignCompletion(User $dcd, float $campaignBudget): void
    {
        // DCD gets 20% of campaign budget as DDS tokens
        $dcdShares = $campaignBudget * 0.20;
        $dcdTokens = $this->getTokenNamesForUser($dcd);

        $this->allocateShares(
            $dcd,
            $dcdShares,
            $dcdTokens['dds'],
            'Campaign completion bonus'
        );

        // Check if DCD was referred by a DA and allocate commission
        $referral = Referral::where('referred_id', $dcd->id)
                          ->where('type', 'da_to_dcd')
                          ->first();

        if ($referral) {
            // DA gets 10% commission of DCD's earnings
            $daCommission = $dcdShares * 0.10;
            $daTokens = $this->getTokenNamesForUser($referral->referrer);

            $this->allocateShares(
                $referral->referrer,
                $daCommission,
                $daTokens['dds'],
                'Commission from DCD campaign completion: ' . $dcd->name
            );
        }
    }

    /**
     * Allocate venture shares when a client submits a campaign
     */
    public function allocateSharesForCampaignSubmission(User $client, float $campaignBudget): void
    {
        // Client gets 5 DWS tokens for submitting a campaign
        $clientTokens = $this->getTokenNamesForUser($client);
        $this->allocateShares(
            $client,
            5,
            $clientTokens['dws'],
            'Campaign submission bonus'
        );
    }

    /**
     * Get country-specific token names for a user
     */
    private function getTokenNamesForUser(User $user): array
    {
        // Load country relationship if not already loaded
        if (!$user->relationLoaded('country')) {
            $user->load('country');
        }
        
        $countryCode = $user->country ? strtoupper($user->country->code) : 'KE'; // Default to Kenya
        
        return [
            'dds' => $countryCode === 'NG' ? 'NgDDS' : 'KeDDS',
            'dws' => $countryCode === 'NG' ? 'NgDWS' : 'KeDWS',
        ];
    }
    
    /**
     * Generic method to allocate venture shares
     */
    private function allocateShares(User $user, float $amount, string $type, string $reason): void
    {
        // Determine if this is a DDS or DWS token based on the type
        $isDDS = str_contains($type, 'DDS');
        $isDWS = str_contains($type, 'DWS');
        
        VentureShare::create([
            'user_id' => $user->id,
            'kedds_amount' => $isDDS ? $amount : 0,
            'kedws_amount' => $isDWS ? $amount : 0,
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