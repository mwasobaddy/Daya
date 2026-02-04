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

        // Get pay per scan - prioritize cost_per_click from campaign if set (> 0)
        $payPerScan = $overrideAmount ?? ($campaign->cost_per_click > 0 ? $campaign->cost_per_click : $this->computePayPerScan($campaign));
        
        Log::info('ScanRewardService: Processing scan - deducting from campaign credit', [
            'scan_id' => $scan->id,
            'campaign_id' => $campaign->id,
            'dcd_id' => $scan->dcd_id,
            'pay_per_scan' => $payPerScan,
            'campaign_credit_before' => $campaign->campaign_credit,
        ]);

        // Strict deduplication: One earning per device fingerprint per campaign
        $fp = $scan->device_fingerprint ?? null;
        if ($fp) {
            $existingEarning = Earning::where('type', 'scan')
                                     ->whereHas('scan', function($query) use ($fp, $scan) {
                                         $query->where('device_fingerprint', $fp)
                                               ->where('campaign_id', $scan->campaign_id);
                                     })
                                     ->first();
            if ($existingEarning) {
                Log::info('ScanRewardService: Blocked scan ' . $scan->id . ' - device already earned from this campaign');
                return null;
            }
        }

        // Strict deduplication: One earning per IP address per campaign (fallback)
        $ip = $scan->geo['ip_address'] ?? null;
        if ($ip) {
            $existingEarningByIp = Earning::where('type', 'scan')
                                         ->whereHas('scan', function($query) use ($ip, $scan) {
                                             $query->whereRaw("JSON_EXTRACT(geo, '$.ip_address') = ?", [$ip])
                                                   ->where('campaign_id', $scan->campaign_id);
                                         })
                                         ->first();
            if ($existingEarningByIp) {
                Log::info('ScanRewardService: Blocked scan ' . $scan->id . ' - IP already earned from this campaign');
                return null;
            }
        }

        // Dedup across recent scans by device fingerprint (helps prevent repeated scans by same device)
        if ($fp) {
            $recent = Scan::where('campaign_id', $scan->campaign_id)
                          ->where('device_fingerprint', $fp)
                          ->where('id', '<', $scan->id)
                          ->where('created_at', '>=', now()->subMinutes(30)) // Reduced from 1 hour to 30 minutes
                          ->orderBy('id', 'desc')
                          ->first();
            if ($recent) {
                Log::info('ScanRewardService: Deduped scan ' . $scan->id . ' due to recent scan with same fingerprint within 30 minutes');
                return null;
            }
        }

        // Additional deduplication by IP address (fallback for fingerprint issues)
        $ip = $scan->geo['ip_address'] ?? null;
        if ($ip) {
            $recentByIp = Scan::where('campaign_id', $scan->campaign_id)
                              ->whereRaw("JSON_EXTRACT(geo, '$.ip_address') = ?", [$ip])
                              ->where('id', '<', $scan->id)
                              ->where('created_at', '>=', now()->subMinutes(10)) // 10 minute window for IP-based dedup
                              ->orderBy('id', 'desc')
                              ->first();
            if ($recentByIp) {
                Log::info('ScanRewardService: Deduped scan ' . $scan->id . ' due to recent scan from same IP within 10 minutes');
                return null;
            }
        }

        // Aggressive deduplication: prevent multiple scans from same IP within 2 minutes for same campaign
        // This catches cases where fingerprinting fails or users try to bypass detection
        if ($ip) {
            $veryRecentByIp = Scan::where('campaign_id', $scan->campaign_id)
                                  ->whereRaw("JSON_EXTRACT(geo, '$.ip_address') = ?", [$ip])
                                  ->where('id', '<', $scan->id)
                                  ->where('created_at', '>=', now()->subMinutes(2))
                                  ->orderBy('id', 'desc')
                                  ->first();
            if ($veryRecentByIp) {
                Log::warning('ScanRewardService: Blocked aggressive duplicate scan ' . $scan->id . ' from IP ' . $ip . ' within 2 minutes');
                return null;
            }
        }

        try {
            // Calculate earnings split: 60% DCD, 30% Company (Daya), 10% Referrer
            // Use rounding to ensure amounts add up correctly
            $dcdAmount = round($payPerScan * 0.60, 2);
            $companyAmount = round($payPerScan * 0.30, 2);
            $referrerAmount = round($payPerScan * 0.10, 2);
            
            // Adjust for rounding errors to ensure total equals payPerScan
            $totalCalculated = $dcdAmount + $companyAmount + $referrerAmount;
            if ($totalCalculated != $payPerScan) {
                $difference = round($payPerScan - $totalCalculated, 2);
                $dcdAmount += $difference; // Add difference to DCD amount
            }

            // Get the DCD user
            $dcd = $scan->dcd;
            if (!$dcd) {
                Log::warning('ScanRewardService: DCD not found for scan ' . $scan->id);
                return null;
            }

            // Create earning for DCD (60%)
            $dcdEarning = Earning::create([
                'user_id' => $dcd->id,
                'campaign_id' => $campaign->id,
                'scan_id' => $scan->id,
                'type' => 'scan',
                'amount' => $dcdAmount,
                'status' => 'pending',
                'description' => "DCD scan reward (60%) for campaign {$campaign->title}",
            ]);

            // Create earning for Company (Daya) (30%)
            $companyUser = \App\Models\User::where('role', 'company')->first();
            if ($companyUser) {
                $companyEarning = Earning::create([
                    'user_id' => $companyUser->id,
                    'campaign_id' => $campaign->id,
                    'scan_id' => $scan->id,
                    'type' => 'scan',
                    'amount' => $companyAmount,
                    'status' => 'pending',
                    'description' => "Company scan reward (30%) for campaign {$campaign->title}",
                ]);
            }

            // Create earning for referrer (10%) - whoever referred this DCD
            $referrer = null;
            $referral = $dcd->referralsReceived()->whereIn('type', ['da_to_dcd', 'admin_to_dcd'])->first();
            if ($referral && $referral->referrer) {
                $referrer = $referral->referrer;
                $referrerEarning = Earning::create([
                    'user_id' => $referrer->id,
                    'campaign_id' => $campaign->id,
                    'scan_id' => $scan->id,
                    'type' => 'scan',
                    'amount' => $referrerAmount,
                    'status' => 'pending',
                    'description' => "Referrer scan reward (10%) for DCD referral - Campaign {$campaign->title}",
                ]);
            }

            // Update the scan's earnings for visibility (total amount)
            $scan->update(['earnings' => $payPerScan]);

            // Deduct from campaign credit and update tracking
            $campaign->decrement('campaign_credit', $payPerScan);
            $campaign->increment('spent_amount', $payPerScan);
            $campaign->increment('total_scans');

            Log::info('ScanRewardService: Created three earnings and deducted from campaign credit', [
                'scan_id' => $scan->id,
                'campaign_id' => $campaign->id,
                'dcd_earning' => $dcdAmount,
                'company_earning' => $companyAmount,
                'referrer_earning' => $referrerAmount,
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

            return $dcdEarning; // Return the DCD earning as the primary earning
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
            case 'apartment_listing':
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
