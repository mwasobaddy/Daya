<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AdminDigestService;
use App\Mail\DailyAdminDigest;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendDailyAdminDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digest:send-admin-daily 
                            {--date= : The date for the digest (Y-m-d format, default: yesterday)}
                            {--email= : Send to specific email instead of all admins}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send daily digest email to admin users with platform statistics';

    protected AdminDigestService $digestService;

    /**
     * Create a new command instance.
     */
    public function __construct(AdminDigestService $digestService)
    {
        parent::__construct();
        $this->digestService = $digestService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Generating daily admin digest...');

        // Parse date option or default to yesterday
        $date = $this->option('date') 
            ? Carbon::parse($this->option('date')) 
            : Carbon::yesterday();

        // Get digest data
        $this->info("Collecting data for {$date->format('M d, Y')}...");
        $digestData = $this->digestService->getDailyDigestData($date);

        // Get recipients
        $recipients = $this->getRecipients();

        if (empty($recipients)) {
            $this->error('No recipients found. Please ensure admin users exist in the system.');
            return Command::FAILURE;
        }

        $this->info('Sending digest to ' . count($recipients) . ' recipient(s)...');

        // Send to each recipient
        $successCount = 0;
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new DailyAdminDigest($digestData));
                $this->info("✓ Sent to {$recipient}");
                $successCount++;
            } catch (\Exception $e) {
                $this->error("✗ Failed to send to {$recipient}: {$e->getMessage()}");
            }
        }

        $this->info("\n" . str_repeat('=', 50));
        $this->info("Daily digest sent successfully!");
        $this->info("Success: {$successCount} / " . count($recipients));
        $this->info("Date: {$digestData['date']}");
        $this->info("Scans: {$digestData['scans']['total']}");
        $this->info("New Campaigns: {$digestData['campaigns']['new_count']}");
        $this->info(str_repeat('=', 50));

        return Command::SUCCESS;
    }

    /**
     * Get list of email recipients
     */
    protected function getRecipients(): array
    {
        // If specific email provided via option
        if ($email = $this->option('email')) {
            return [$email];
        }

        // Otherwise, get all admin users
        $admins = User::where('role', 'admin')->pluck('email')->toArray();

        return $admins;
    }
}
