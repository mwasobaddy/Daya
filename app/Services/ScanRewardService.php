<?php

namespace App\Services;

use App\Models\Scan;
use App\Models\Campaign;
use App\Models\Earning;
use Illuminate\Support\Facades\Log;

class ScanRewardService
{
    /**
     * Credit a scan reward for the given Scan object.
     * Returns the created Earning or null if deduped/none created.
     */
    public function creditScanReward(Scan $scan, ?float $overrideAmount = null): ?Earning
    {
        // Dedup: make sure we don't credit the same scan twice by scan_id
        $existing = Earning::where('type', 'scan')->where('scan_id', $scan->id)->first();
        if ($existing) {
            return null;
        }

        $campaign = $scan->campaign()->first();
        if (! $campaign) {
            Log::warning('ScanRewardService: campaign not found for scan ' . $scan->id);
            return null;
        }

        // Check if campaign can still accept scans (budget not exhausted)
        if (!$campaign->canAcceptScans()) {
            Log::info('ScanRewardService: Campaign ' . $campaign->id . ' cannot accept more scans (budget exhausted or limit reached)');
            
            // Auto-complete campaign if not already completed
            if ($campaign->status !== 'completed') {
                $campaign->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                Log::info('ScanRewardService: Auto-completed campaign ' . $campaign->id . ' due to budget/scan limit');
            }
            
            return null;
        }

        // Ensure scan belongs to configured campaign/dcd
        if ($campaign->dcd_id !== $scan->dcd_id) {
            Log::warning('ScanRewardService: scan dcd_id does not match campaign dcd_id', [
                'scan_id' => $scan->id,
                'scan_dcd_id' => $scan->dcd_id,
                'campaign_dcd_id' => $campaign->dcd_id,
                'scan_dcd_type' => gettype($scan->dcd_id),
                'campaign_dcd_type' => gettype($campaign->dcd_id),
            ]);
            return null;
        }

        // Get pay per scan - prioritize cost_per_click from campaign
        $payPerScan = $overrideAmount ?? $campaign->cost_per_click ?? $this->computePayPerScan($campaign);
        
        Log::info('ScanRewardService: Processing scan - deducting from campaign credit', [
            'scan_id' => $scan->id,
            'campaign_id' => $campaign->id,
            'dcd_id' => $scan->dcd_id,
            'pay_per_scan' => $payPerScan,
            'campaign_credit_before' => $campaign->campaign_credit,
        ]);

        // Dedup across recent scans by device fingerprint (helps prevent repeated scans by same device)
        $fp = $scan->device_fingerprint ?? null;
        if ($fp) {
            $recent = Scan::where('campaign_id', $scan->campaign_id)
                          ->where('device_fingerprint', $fp)
                          ->where('id', '<', $scan->id)
                          ->where('created_at', '>=', now()->subHours(1))
                          ->orderBy('id', 'desc')
                          ->first();
            if ($recent) {
                Log::info('ScanRewardService: Deduped scan ' . $scan->id . ' due to recent scan with same fingerprint');
                return null;
            }
        }

        try {
            // NO NEW EARNINGS - Earnings were distributed upfront when campaign was approved
            // We only deduct from campaign_credit and track the scan
            
            // Update the scan's earnings for visibility
            $scan->update(['earnings' => $payPerScan]);

            // Deduct from campaign credit and update tracking
            $campaign->decrement('campaign_credit', $payPerScan);
            $campaign->increment('spent_amount', $payPerScan);
            $campaign->increment('total_scans');

            Log::info('ScanRewardService: Deducted from campaign credit', [
                'campaign_id' => $campaign->id,
                'deducted' => $payPerScan,
                'campaign_credit_after' => $campaign->fresh()->campaign_credit,
            ]);

            // Check if campaign should be auto-completed after this scan
            $campaign->refresh();
            if (!$campaign->canAcceptScans() && $campaign->status !== 'completed') {
                $campaign->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                Log::info('ScanRewardService: Auto-completed campaign ' . $campaign->id . ' after exhausting credit/scan limit');
            }

            // Return null since no new earning was created (upfront distribution model)
            return null;
        } catch (\Exception $e) {
            Log::warning('ScanRewardService: failed to process scan - ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Compute the per-scan payout amount for a campaign according to Earning.md
     */
    public function computePayPerScan(Campaign $campaign): float
    {
        $metadata = $campaign->metadata ?? [];
        if (isset($metadata['pay_per_scan'])) {
            return (float) $metadata['pay_per_scan'];
        }

        $objective = $campaign->campaign_objective ?? null;
        $explainer = $campaign->explainer_video_url ?? $metadata['explainer_video_url'] ?? null;

        // Get base pay per scan in Kenyan Shillings
        switch ($objective) {
            case 'music_promotion':
                $basePay = 1.0;
                break;
            case 'app_downloads':
                $basePay = 5.0;
                break;
            case 'product_launch':
                $basePay = 5.0;
                break;
            case 'brand_awareness':
                $basePay = $explainer ? 5.0 : 1.0;
                break;
            case 'event_promotion':
                $basePay = $explainer ? 5.0 : 1.0;
                break;
            case 'social_cause':
                $basePay = $explainer ? 5.0 : 1.0;
                break;
            default:
                $basePay = 1.0;
        }

        // Adjust for currency: 1 KSh = 10 Naira
        $client = $campaign->client;
        if ($client && $client->country && $client->country->code === 'NG') {
            return $basePay * 10;
        }

        return $basePay;
    }

    /**
     * Credit DA commission (10% of campaign budget) when a client they referred creates a campaign.
     * This should be called when a campaign is approved/launched.
     */
    public static function creditDaCommissionForCampaign(Campaign $campaign): ?Earning
    {
        // Find the client
        $client = $campaign->client;
        if (!$client) {
            Log::warning('ScanRewardService: No client found for campaign ' . $campaign->id);
            return null;
        }

        // Check if client was referred by a DA
        $referral = \App\Models\Referral::where('referred_id', $client->id)
            ->where('type', 'da_to_client')
            ->first();

        if (!$referral) {
            Log::info('ScanRewardService: No DA referral found for client ' . $client->id);
            return null;
        }

        $da = $referral->referrer;
        if (!$da || $da->role !== 'da') {
            Log::warning('ScanRewardService: Invalid DA referrer for client ' . $client->id);
            return null;
        }

        // Calculate 10% of campaign budget (changed from 5%)
        $commissionAmount = round($campaign->budget * 0.10, 2);

        if ($commissionAmount <= 0) {
            Log::warning('ScanRewardService: Campaign budget too low for DA commission');
            return null;
        }

        // Check if commission already exists for this campaign
        $existing = Earning::where('user_id', $da->id)
            ->where('campaign_id', $campaign->id)
            ->where('type', 'commission')
            ->first();

        if ($existing) {
            Log::info('ScanRewardService: DA commission already credited for campaign ' . $campaign->id);
            return $existing;
        }

        try {
            // Create DA commission earning
            $daEarning = Earning::create([
                'user_id' => $da->id,
                'campaign_id' => $campaign->id,
                'scan_id' => null,
                'amount' => $commissionAmount,
                'commission_amount' => $commissionAmount,
                'type' => 'commission',
                'description' => 'DA commission (10% of budget) for client referral - Campaign: ' . $campaign->title,
                'status' => 'pending',
            ]);

            Log::info('ScanRewardService: DA commission credited for campaign', [
                'da_id' => $da->id,
                'client_id' => $client->id,
                'campaign_id' => $campaign->id,
                'commission' => $commissionAmount,
                'budget' => $campaign->budget,
            ]);

            return $daEarning;
        } catch (\Exception $e) {
            Log::error('ScanRewardService: Failed to credit DA commission - ' . $e->getMessage());
            return null;
        }
    }
}
