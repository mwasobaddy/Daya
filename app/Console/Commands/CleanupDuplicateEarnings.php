<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Earning;
use App\Models\Campaign;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateEarnings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'earnings:cleanup-duplicates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove duplicate earnings (keep oldest) and recalculate campaign spent amounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting cleanup of duplicate earnings...');

        // Find duplicate earnings by scan_id
        $duplicates = Earning::select('scan_id', DB::raw('COUNT(*) as count'))
            ->where('type', 'scan')
            ->whereNotNull('scan_id')
            ->groupBy('scan_id')
            ->having('count', '>', 1)
            ->get();

        if ($duplicates->isEmpty()) {
            $this->info('No duplicate earnings found.');
        } else {
            $this->info("Found {$duplicates->count()} scans with duplicate earnings.");
            
            $totalDeleted = 0;

            foreach ($duplicates as $duplicate) {
                // Get all earnings for this scan
                $earnings = Earning::where('scan_id', $duplicate->scan_id)
                    ->where('type', 'scan')
                    ->orderBy('id', 'asc')
                    ->get();

                // Keep the first one, delete the rest
                $kept = $earnings->first();
                $toDelete = $earnings->slice(1);

                foreach ($toDelete as $earning) {
                    $this->line("  ✗ Deleting duplicate earning #{$earning->id} for scan #{$earning->scan_id}");
                    $earning->delete();
                    $totalDeleted++;
                }
            }

            $this->info("Deleted {$totalDeleted} duplicate earnings.");
        }

        // Now recalculate campaign spent amounts based on remaining earnings
        $this->newLine();
        $this->info('Recalculating campaign spent amounts...');

        $campaigns = Campaign::all();

        foreach ($campaigns as $campaign) {
            $totalEarnings = Earning::where('campaign_id', $campaign->id)
                ->where('type', 'scan')
                ->sum('amount');
            
            $actualScans = Earning::where('campaign_id', $campaign->id)
                ->where('type', 'scan')
                ->count();
            
            $oldSpent = $campaign->spent_amount;
            $oldScans = $campaign->total_scans;

            $campaign->update([
                'spent_amount' => $totalEarnings,
                'total_scans' => $actualScans,
            ]);
            
            if ($oldSpent != $totalEarnings || $oldScans != $actualScans) {
                $this->line("✓ Campaign #{$campaign->id} ({$campaign->title}):");
                $this->line("  Scans: {$oldScans} → {$actualScans}");
                $this->line("  Spent: KSh {$oldSpent} → KSh {$totalEarnings}");
            }
        }

        $this->newLine();
        $this->info('==================================================');
        $this->info('Cleanup completed!');
        $this->info('==================================================');

        return 0;
    }
}