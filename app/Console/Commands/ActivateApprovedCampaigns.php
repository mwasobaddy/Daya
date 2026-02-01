<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ActivateApprovedCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:activate-approved {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate approved campaigns when their start date arrives or has passed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $today = now()->format('Y-m-d');

        if ($isDryRun) {
            $this->info('🔍 Running in dry-run mode - no changes will be made');
        }

        // Find approved campaigns where start_date <= today
        $campaignsToActivate = Campaign::query()
            ->where('status', 'approved')
            ->whereRaw("JSON_EXTRACT(metadata, '$.start_date') <= ?", [$today])
            ->get();

        if ($campaignsToActivate->isEmpty()) {
            $this->info('✅ No approved campaigns need activation today');
            return;
        }

        $this->info("📅 Found {$campaignsToActivate->count()} approved campaigns to activate");

        foreach ($campaignsToActivate as $campaign) {
            $startDate = $campaign->metadata['start_date'] ?? null;

            if ($isDryRun) {
                $this->line("🔄 Would activate campaign {$campaign->id} ({$campaign->title}) - Start date: {$startDate}");
            } else {
                $campaign->update(['status' => 'active']);
                Log::info("Activated approved campaign {$campaign->id} - start date reached: {$startDate}");
                $this->line("✅ Activated campaign {$campaign->id} ({$campaign->title})");
            }
        }

        if (!$isDryRun) {
            $this->info("🎉 Successfully activated {$campaignsToActivate->count()} campaigns");
        }
    }
}
