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
        $existing = Earning::where('type', 'scan')->where('related_id', $scan->id)->first();
        if ($existing) {
            return null;
        }

        $campaign = $scan->campaign()->first();
        if (! $campaign) {
            Log::warning('ScanRewardService: campaign not found for scan ' . $scan->id);
            return null;
        }

        // Ensure scan belongs to configured campaign/dcd
        if ($campaign->dcd_id !== $scan->dcd_id) {
            Log::warning('ScanRewardService: scan dcd_id does not match campaign dcd_id', ['scan_id' => $scan->id]);
            return null;
        }

        // Compute pay per scan with override precedence
        $payPerScan = $overrideAmount ?? $this->computePayPerScan($campaign);

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
                    return null;
                }
            }
        }

        try {
            $earning = Earning::create([
                'user_id' => $scan->dcd_id,
                'amount' => $payPerScan,
                'type' => 'scan',
                'description' => 'Scan reward for campaign: ' . $campaign->title,
                'related_id' => $scan->id,
                'status' => 'pending',
                'month' => now()->format('Y-m'),
            ]);

            // Update the scan's earnings for visibility
            $scan->update(['earnings' => $payPerScan]);

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
}
