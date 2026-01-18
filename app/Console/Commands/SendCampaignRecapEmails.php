<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Campaign;
use App\Models\User;
use App\Mail\CampaignRecap;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SendCampaignRecapEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaigns:send-recap-emails {--dry-run : Run without sending emails}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send recap emails for recently completed campaigns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        $this->info('========================================');
        $this->info('Campaign Recap Email Job Started');
        $this->info('Time: ' . now()->format('Y-m-d H:i:s'));
        $this->info('========================================');

        // Find campaigns that completed in the last 12 hours and haven't been notified yet
        // We use 12 hours to catch campaigns that completed since the last run
        $completedCampaigns = Campaign::where('status', 'completed')
            ->where('completed_at', '>=', now()->subHours(12))
            ->where('completed_at', '<=', now())
            ->with(['client', 'dcd'])
            ->get()
            ->filter(function ($campaign) {
                // Filter out campaigns that already have recap email sent
                $metadata = $campaign->metadata ?? [];
                return !isset($metadata['recap_email_sent']) || $metadata['recap_email_sent'] !== true;
            });

        if ($completedCampaigns->isEmpty()) {
            $this->info('No completed campaigns found that need recap emails.');
            return Command::SUCCESS;
        }

        $this->info("Found {$completedCampaigns->count()} completed campaigns to process.");
        
        $successCount = 0;
        $failureCount = 0;
        $notifiedUsers = [];

        foreach ($completedCampaigns as $campaign) {
            try {
                // Check if recap email already sent
                $metadata = $campaign->metadata ?? [];
                if (isset($metadata['recap_email_sent']) && $metadata['recap_email_sent'] === true) {
                    $this->line("  ⊘ Campaign #{$campaign->id}: Recap already sent, skipping...");
                    continue;
                }

                $this->line("Processing Campaign #{$campaign->id}: {$campaign->title}");

                $recipients = [];

                // Send to client
                if ($campaign->client) {
                    $recipients[] = $campaign->client;
                    if (!$dryRun) {
                        Mail::to($campaign->client->email)->send(
                            new CampaignRecap($campaign, $campaign->client, 'client')
                        );
                    }
                    $this->line("  ✓ Sent to Client: {$campaign->client->email}");
                }

                // Send to DCD
                if ($campaign->dcd) {
                    $recipients[] = $campaign->dcd;
                    if (!$dryRun) {
                        Mail::to($campaign->dcd->email)->send(
                            new CampaignRecap($campaign, $campaign->dcd, 'dcd')
                        );
                    }
                    $this->line("  ✓ Sent to DCD: {$campaign->dcd->email}");
                }

                // Send to DA if exists (whoever referred the DCD)
                if ($campaign->dcd) {
                    $referral = $campaign->dcd->referralsReceived()
                        ->whereIn('type', ['da_to_dcd', 'admin_to_dcd'])
                        ->first();
                    
                    if ($referral && $referral->referrer) {
                        $da = $referral->referrer;
                        $recipients[] = $da;
                        if (!$dryRun) {
                            Mail::to($da->email)->send(
                                new CampaignRecap($campaign, $da, $da->role === 'admin' ? 'admin' : 'da')
                            );
                        }
                        $this->line("  ✓ Sent to DA/Admin: {$da->email}");
                    }
                }

                // Mark campaign as notified
                if (!$dryRun) {
                    $metadata['recap_email_sent'] = true;
                    $metadata['recap_email_sent_at'] = now()->toDateTimeString();
                    $campaign->update(['metadata' => $metadata]);
                }

                $successCount++;
                $notifiedUsers = array_merge($notifiedUsers, $recipients);

                Log::info("Campaign recap emails sent for campaign #{$campaign->id}", [
                    'campaign_id' => $campaign->id,
                    'campaign_title' => $campaign->title,
                    'recipients_count' => count($recipients),
                ]);

            } catch (\Exception $e) {
                $failureCount++;
                $this->error("  ✗ Failed for Campaign #{$campaign->id}: {$e->getMessage()}");
                Log::error("Failed to send campaign recap emails for campaign #{$campaign->id}", [
                    'campaign_id' => $campaign->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $uniqueUsersNotified = collect($notifiedUsers)->unique('id')->count();

        $this->newLine();
        $this->info('========================================');
        $this->info('Campaign Recap Job Summary');
        $this->info('========================================');
        $this->info("Campaigns Processed: {$completedCampaigns->count()}");
        $this->info("Successfully Sent: {$successCount}");
        $this->info("Failed: {$failureCount}");
        $this->info("Unique Users Notified: {$uniqueUsersNotified}");
        if ($dryRun) {
            $this->warn('DRY RUN MODE - No emails were actually sent');
        }
        $this->info('========================================');

        return Command::SUCCESS;
    }
}
