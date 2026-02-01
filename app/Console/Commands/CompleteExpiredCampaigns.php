<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use Illuminate\Console\Command;

class CompleteExpiredCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'daya:complete-expired-campaigns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark live campaigns that have reached their end date as completed';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = now()->format('Y-m-d');
        
        // Find live campaigns where end_date has passed
        $expiredCampaigns = Campaign::where('status', 'live')
            ->whereRaw("JSON_EXTRACT(metadata, '$.end_date') < ?", [$today])
            ->get();
        
        $count = 0;
        foreach ($expiredCampaigns as $campaign) {
            $campaign->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);
            $count++;
        }
        
        $this->info("Marked {$count} expired campaigns as completed.");
        
        return 0;
    }
}
