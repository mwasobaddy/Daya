<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Services\CampaignMatchingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MatchUnassignedCampaigns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:match-unassigned {--dry-run : Run without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for active campaigns without assigned DCDs and attempt to match them';

    protected CampaignMatchingService $matchingService;

    public function __construct(CampaignMatchingService $matchingService)
    {
        parent::__construct();
        $this->matchingService = $matchingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 Running in dry-run mode - no changes will be made');
        }

        // Find active campaigns that don't have a DCD assigned
        $unassignedCampaigns = Campaign::query()
            ->where('status', 'active')
            ->whereNull('dcd_id')
            ->orderBy('updated_at', 'asc') // Use updated_at since approved_at might not exist for active campaigns
            ->get();

        if ($unassignedCampaigns->isEmpty()) {
            $this->info('✅ No unassigned active campaigns found.');
            Log::info('campaigns:match-unassigned completed - no campaigns to process');
            return 0;
        }

        $this->info("📋 Found {$unassignedCampaigns->count()} unassigned active campaigns");
        
        $matchedCount = 0;
        $unmatchedCount = 0;
        $unmatchedCampaigns = [];

        foreach ($unassignedCampaigns as $campaign) {
            $client = $campaign->client;
            $clientName = $client ? $client->name : 'Unknown Client';
            
            $this->line("  Processing Campaign #{$campaign->id} ({$clientName})...");

            if ($isDryRun) {
                $this->warn("    [DRY-RUN] Would attempt to match this campaign");
                continue;
            }

            // Attempt to assign a DCD
            $assignedDcd = $this->matchingService->assignDcd($campaign);

            if ($assignedDcd) {
                $matchedCount++;
                $this->info("    ✅ Matched with DCD: {$assignedDcd->name} ({$assignedDcd->email})");

                // Send notifications
                $this->sendMatchNotifications($campaign, $assignedDcd);

                Log::info('Campaign matched with DCD', [
                    'campaign_id' => $campaign->id,
                    'dcd_id' => $assignedDcd->id,
                    'dcd_name' => $assignedDcd->name,
                    'client_name' => $clientName
                ]);
            } else {
                $unmatchedCount++;
                $unmatchedCampaigns[] = [
                    'id' => $campaign->id,
                    'client' => $clientName,
                    'budget' => $campaign->budget
                ];
                
                $this->warn("    ⚠️  No suitable DCD found - will retry tomorrow");
                
                Log::warning('Campaign could not be matched with any DCD', [
                    'campaign_id' => $campaign->id,
                    'client_name' => $clientName,
                    'business_types' => $campaign->metadata['business_types'] ?? null,
                    'music_genres' => $campaign->metadata['music_genres'] ?? null
                ]);
            }
        }

        // Summary
        $this->newLine();
        $this->info('📊 Matching Summary:');
        $this->table(
            ['Status', 'Count'],
            [
                ['Matched', $matchedCount],
                ['Unmatched', $unmatchedCount],
                ['Total Processed', $unassignedCampaigns->count()]
            ]
        );

        if (!empty($unmatchedCampaigns)) {
            $this->newLine();
            $this->warn('⚠️  Campaigns still awaiting DCD assignment:');
            $this->table(
                ['Campaign ID', 'Client', 'Budget'],
                collect($unmatchedCampaigns)->map(fn($c) => [
                    $c['id'],
                    $c['client'],
                    '$' . number_format($c['budget'], 2)
                ])->toArray()
            );
        }

        Log::info('campaigns:match-unassigned completed', [
            'matched' => $matchedCount,
            'unmatched' => $unmatchedCount,
            'total' => $unassignedCampaigns->count()
        ]);

        return 0;
    }

    /**
     * Send notifications when a campaign is matched with a DCD
     */
    private function sendMatchNotifications(Campaign $campaign, $dcd): void
    {
        $client = $campaign->client;

        // Notify the DCD
        try {
            \Illuminate\Support\Facades\Mail::to($dcd->email)
                ->queue(new \App\Mail\CampaignAssigned($campaign, $client));
        } catch (\Exception $e) {
            Log::error('Failed to queue campaign assignment email to DCD', [
                'dcd_email' => $dcd->email,
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);
        }

        // Notify the client
        if ($client) {
            try {
                \Illuminate\Support\Facades\Mail::to($client->email)
                    ->queue(new \App\Mail\CampaignMatched($campaign, $dcd));
            } catch (\Exception $e) {
                Log::error('Failed to queue campaign matched email to client', [
                    'client_email' => $client->email,
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Notify admins (get all admin users)
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            try {
                \Illuminate\Support\Facades\Mail::to($admin->email)
                    ->queue(new \App\Mail\AdminCampaignMatched($campaign, $dcd, $client));
            } catch (\Exception $e) {
                Log::error('Failed to queue campaign matched email to admin', [
                    'admin_email' => $admin->email,
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
