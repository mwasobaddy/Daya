<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Scan;
use App\Models\Earning;
use App\Models\Campaign;
use App\Services\ScanRewardService;

class BackfillScanEarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scans:backfill-earnings {--campaign_id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retroactively process scans that don\'t have earnings and update campaign spent_amount';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $campaignId = $this->option('campaign_id');
        
        $this->info('Starting backfill of scan earnings...');

        // Get scans query
        $scansQuery = Scan::with(['campaign', 'dcd']);
        
        if ($campaignId) {
            $scansQuery->where('campaign_id', $campaignId);
            $this->info("Processing scans for Campaign ID: {$campaignId}");
        }
        
        $scans = $scansQuery->orderBy('id', 'asc')->get();

        if ($scans->isEmpty()) {
            $this->info('No scans found.');
            return 0;
        }

        $this->info("Found {$scans->count()} scans to check.");
        
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($scans->count());
        $progressBar->start();

        $service = app(ScanRewardService::class);

        foreach ($scans as $scan) {
            $progressBar->advance();

            // Check if earning already exists for this scan
            $existing = Earning::where('type', 'scan')
                ->where('scan_id', $scan->id)
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            // Check if campaign exists and can accept scans
            if (!$scan->campaign) {
                $failed++;
                continue;
            }

            // Temporarily allow processing even if campaign is completed (for backfill)
            $campaign = $scan->campaign;
            $originalStatus = $campaign->status;
            
            // Get the cost per click for this campaign
            $costPerClick = $campaign->cost_per_click ?? 1.0;

            try {
                // Create the earning directly for backfill
                $earning = Earning::create([
                    'user_id' => $scan->dcd_id,
                    'campaign_id' => $campaign->id,
                    'scan_id' => $scan->id,
                    'amount' => $costPerClick,
                    'commission_amount' => 0,
                    'type' => 'scan',
                    'description' => 'Scan reward for campaign: ' . $campaign->title . ' (backfilled)',
                    'status' => 'pending',
                ]);

                // Update the scan's earnings
                $scan->update(['earnings' => $costPerClick]);

                // Update campaign spent amount and total scans (recalculate properly)
                $campaign->increment('spent_amount', $costPerClick);

                $processed++;
                
                if ($processed % 10 === 0) {
                    $this->newLine();
                    $this->line("✓ Processed {$processed} scans so far...");
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("✗ Failed for Scan #{$scan->id}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Recalculate campaign spent amounts based on actual earnings
        $this->info('Recalculating campaign spent amounts...');
        
        $campaignsQuery = Campaign::query();
        if ($campaignId) {
            $campaignsQuery->where('id', $campaignId);
        }
        
        $campaigns = $campaignsQuery->get();
        
        foreach ($campaigns as $campaign) {
            $totalEarnings = Earning::where('campaign_id', $campaign->id)
                ->where('type', 'scan')
                ->sum('amount');
            
            $actualScans = Earning::where('campaign_id', $campaign->id)
                ->where('type', 'scan')
                ->count();
            
            $campaign->update([
                'spent_amount' => $totalEarnings,
                'total_scans' => $actualScans,
            ]);
            
            $this->line("✓ Campaign #{$campaign->id} ({$campaign->title}): {$actualScans} scans, KSh {$totalEarnings} spent");
        }

        // Summary
        $this->newLine();
        $this->info('==================================================');
        $this->info('Backfill completed!');
        $this->info("Processed: {$processed} scans");
        $this->info("Skipped: {$skipped} scans (already have earnings)");
        if ($failed > 0) {
            $this->warn("Failed: {$failed} scans");
        }
        $this->info('==================================================');

        return 0;
    }
}