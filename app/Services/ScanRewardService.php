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
        // Dedup: make sure we don't credit the same scan twice by related scan id
        $existing = Earning::where('type', 'scan_earning')->where('scan_id', $scan->id)->first();
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
        
        Log::info('ScanRewardService: Processing scan reward', [
            'scan_id' => $scan->id,
            'campaign_id' => $campaign->id,
            'dcd_id' => $scan->dcd_id,
            'pay_per_scan' => $payPerScan,
        ]);

        // Get pay per scan - prioritize cost_per_click from campaign
        $payPerScan = $overrideAmount ?? $campaign->cost_per_click ?? $this->computePayPerScan($campaign);

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
                // If there's an existing earning for the recent scan, dedupe
                $existingRecentEarning = Earning::where('type', 'scan')->where('related_id', $recent->id)->first();
                if ($existingRecentEarning) {
                    Log::info('ScanRewardService: Deduped scan ' . $scan->id . ' due to recent scan with same fingerprint');
                    return null;
                }
            }
        }

        try {
            // Create DCD earning
            $earning = Earning::create([
                'user_id' => $scan->dcd_id,
                'campaign_id' => $campaign->id,
                'scan_id' => $scan->id,
                'amount' => $payPerScan,
                'commission_amount' => 0,
                'type' => 'scan_earning',
                'description' => 'Scan reward for campaign: ' . $campaign->title,
                'status' => 'pending',
            ]);

            // Update the scan's earnings for visibility
            $scan->update(['earnings' => $payPerScan]);

            // Update campaign spent amount and total scans
            $campaign->increment('spent_amount', $payPerScan);
            $campaign->increment('total_scans');

            // Calculate and create DA commission (5% of DCD earnings)
            $this->creditDaCommission($scan, $earning, $payPerScan);

            // Check if campaign should be auto-completed after this scan
            $campaign->refresh();
            if (!$campaign->canAcceptScans() && $campaign->status !== 'completed') {
                $campaign->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);
                Log::info('ScanRewardService: Auto-completed campaign ' . $campaign->id . ' after reaching budget/scan limit');
            }

            return $earning;
        } catch (\Exception $e) {
            Log::warning('ScanRewardService: failed to create earning - ' . $e->getMessage());
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
     * Credit DA commission (5% of DCD earnings)
     */
    protected function creditDaCommission(Scan $scan, Earning $dcdEarning, float $dcdAmount): ?Earning
    {
        // Find the DCD
        $dcd = $scan->dcd;
        if (!$dcd) {
            return null;
        }

        // Check if DCD was referred by a DA
        $referral = \App\Models\Referral::where('referred_id', $dcd->id)
            ->where('referral_type', 'da_to_dcd')
            ->first();

        if (!$referral) {
            Log::info('ScanRewardService: No DA referral found for DCD ' . $dcd->id);
            return null;
        }

        $da = $referral->referrer;
        if (!$da || $da->role !== 'da') {
            Log::warning('ScanRewardService: Invalid DA referrer for DCD ' . $dcd->id);
            return null;
        }

        // Calculate 5% commission
        $commissionAmount = round($dcdAmount * 0.05, 2);

        if ($commissionAmount <= 0) {
            return null;
        }

        try {
            // Create DA commission earning
            $daEarning = Earning::create([
                'user_id' => $da->id,
                'campaign_id' => $scan->campaign_id,
                'scan_id' => $scan->id,
                'amount' => $commissionAmount,
                'commission_amount' => $commissionAmount,
                'type' => 'commission',
                'description' => 'DA commission (5%) for DCD ' . $dcd->name . ' scan in campaign: ' . $scan->campaign->title,
                'status' => 'pending',
            ]);

            Log::info('ScanRewardService: DA commission credited', [
                'da_id' => $da->id,
                'dcd_id' => $dcd->id,
                'commission' => $commissionAmount,
                'dcd_earning' => $dcdAmount,
            ]);

            return $daEarning;
        } catch (\Exception $e) {
            Log::error('ScanRewardService: Failed to credit DA commission - ' . $e->getMessage());
            return null;
        }
    }
}
