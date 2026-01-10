<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Models\Earning;
use App\Models\Referral;
use App\Services\ScanRewardService;

class BackfillDaCommissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:backfill-da-commissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill DA commissions (5% of campaign budget) for existing approved campaigns where client was referred by a DA';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill of DA commissions for existing campaigns...');

        // Get all approved campaigns
        $campaigns = Campaign::whereIn('status', ['approved', 'live', 'completed'])
            ->with('client')
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No campaigns found.');
            return 0;
        }

        $this->info("Found {$campaigns->count()} campaigns to check.");
        
        $credited = 0;
        $skipped = 0;
        $failed = 0;

        $progressBar = $this->output->createProgressBar($campaigns->count());
        $progressBar->start();

        foreach ($campaigns as $campaign) {
            $progressBar->advance();

            // Check if client exists
            if (!$campaign->client) {
                $skipped++;
                continue;
            }

            // Check if client was referred by a DA
            $referral = Referral::where('referred_id', $campaign->client_id)
                ->where('type', 'da_to_client')
                ->first();

            if (!$referral) {
                $skipped++;
                continue;
            }

            $da = $referral->referrer;
            if (!$da || $da->role !== 'da') {
                $skipped++;
                continue;
            }

            // Check if commission already exists
            $existing = Earning::where('user_id', $da->id)
                ->where('campaign_id', $campaign->id)
                ->where('type', 'commission')
                ->first();

            if ($existing) {
                $skipped++;
                continue;
            }

            // Credit the DA commission
            try {
                $earning = ScanRewardService::creditDaCommissionForCampaign($campaign);
                
                if ($earning) {
                    $credited++;
                    $this->newLine();
                    $this->line("✓ Credited DA #{$da->id} ({$da->name}): KSh {$earning->amount} for Campaign #{$campaign->id} ({$campaign->title})");
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                $this->newLine();
                $this->error("✗ Failed for Campaign #{$campaign->id}: " . $e->getMessage());
            }
        }

        $progressBar->finish();
        $this->newLine(2);

        // Summary
        $this->info('==================================================');
        $this->info('Backfill completed!');
        $this->info("Credited: {$credited} campaigns");
        $this->info("Skipped: {$skipped} campaigns (no DA referral or already credited)");
        if ($failed > 0) {
            $this->warn("Failed: {$failed} campaigns");
        }
        $this->info('==================================================');

        return 0;
    }
}
