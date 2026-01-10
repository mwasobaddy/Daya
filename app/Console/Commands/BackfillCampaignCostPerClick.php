<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;

class BackfillCampaignCostPerClick extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:backfill-cost-per-click';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill cost_per_click and max_scans for existing campaigns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting backfill of cost_per_click for existing campaigns...');

        // Find campaigns without cost_per_click set
        $campaigns = Campaign::whereNull('cost_per_click')
            ->orWhere('cost_per_click', 0)
            ->get();

        if ($campaigns->isEmpty()) {
            $this->info('No campaigns need backfilling. All campaigns have cost_per_click set.');
            return Command::SUCCESS;
        }

        $this->info('Found ' . $campaigns->count() . ' campaigns to backfill.');

        $updated = 0;
        foreach ($campaigns as $campaign) {
            $costPerClick = $this->calculateCostPerClick($campaign);
            $maxScans = $costPerClick > 0 ? floor($campaign->budget / $costPerClick) : 0;

            $campaign->update([
                'cost_per_click' => $costPerClick,
                'max_scans' => $maxScans,
            ]);

            $this->info("✓ Updated Campaign #{$campaign->id}: {$campaign->title}");
            $this->line("  - Cost per click: KSh {$costPerClick}");
            $this->line("  - Max scans: {$maxScans}");
            $updated++;
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("Backfill completed!");
        $this->info("Updated {$updated} campaigns");
        $this->info(str_repeat('=', 50));

        return Command::SUCCESS;
    }

    /**
     * Calculate cost per click based on campaign objective
     */
    protected function calculateCostPerClick(Campaign $campaign): float
    {
        $objective = $campaign->campaign_objective ?? 'music_promotion';
        $explainerVideo = $campaign->explainer_video_url ?? $campaign->metadata['explainer_video_url'] ?? null;
        $countryCode = $campaign->metadata['country'] ?? 'KE';

        // Base rates in Kenyan Shillings (per Earning.md)
        $baseRate = match($objective) {
            'music_promotion' => 1.0,
            'app_downloads' => 5.0,
            'product_launch' => 5.0,
            'brand_awareness' => $explainerVideo ? 5.0 : 1.0,
            'event_promotion' => $explainerVideo ? 5.0 : 1.0,
            'social_cause' => $explainerVideo ? 5.0 : 1.0,
            default => 1.0,
        };

        // Adjust for currency: 1 KSh = 10 Naira
        if (strtoupper($countryCode) === 'NG') {
            return $baseRate * 10;
        }

        return $baseRate;
    }
}

