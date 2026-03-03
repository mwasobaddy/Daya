<?php

namespace App\Console\Commands;

use App\Models\Scan;
use App\Services\ScanRewardService;
use Illuminate\Console\Command;

class ProcessUnprocessedScans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scans:process-unprocessed {--batch=10 : Number of scans to process per batch} {--dry-run : Show what would be processed without actually processing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process unprocessed scans (earnings = 0) that are not duplicates';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $batchSize = (int) $this->option('batch');
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? 'DRY RUN MODE - No changes will be made' : 'Processing unprocessed scans...');

        // Get unprocessed scans
        $unprocessedScans = Scan::where('earnings', 0)
            ->orderBy('created_at', 'asc')
            ->take($batchSize)
            ->get();

        if ($unprocessedScans->isEmpty()) {
            $this->info('No unprocessed scans found.');
            return;
        }

        $this->info("Found {$unprocessedScans->count()} unprocessed scans to check.");

        $service = new ScanRewardService();
        $processed = 0;
        $skipped = 0;

        $this->output->progressStart($unprocessedScans->count());

        foreach ($unprocessedScans as $scan) {
            if ($dryRun) {
                // In dry run, just check if it would be processed
                $existing = \App\Models\Earning::where('type', 'scan')
                    ->where('scan_id', $scan->id)
                    ->first();

                if ($existing) {
                    $this->line("Scan {$scan->id}: Already has earning - SKIPPED");
                    $skipped++;
                } else {
                    $this->line("Scan {$scan->id}: Would be processed");
                    $processed++;
                }
            } else {
                // Actually process the scan
                $result = $service->creditScanReward($scan);

                if ($result) {
                    $this->line("Scan {$scan->id}: Processed successfully - Earnings: {$scan->fresh()->earnings}");
                    $processed++;
                } else {
                    $this->line("Scan {$scan->id}: Skipped (duplicate or invalid)");
                    $skipped++;
                }
            }

            $this->output->progressAdvance();
        }

        $this->output->progressFinish();

        $this->info("Batch complete: {$processed} processed, {$skipped} skipped");

        if (!$dryRun && $processed > 0) {
            $remaining = Scan::where('earnings', 0)->count();
            $this->info("{$remaining} scans still unprocessed. Run again to process more.");
        }
    }
}
