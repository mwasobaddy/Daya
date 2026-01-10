<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Scan;
use App\Models\Earning;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SendScanMonitoringDigest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digest:scan-monitoring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scan monitoring digest to admin (User #1) every 5 minutes to track earnings system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $admin = User::find(1);
        
        if (!$admin) {
            $this->error('Admin user (ID: 1) not found');
            return 1;
        }

        $this->info('Generating scan monitoring digest...');

        // Get recent scans (last 5 minutes)
        $recentScans = Scan::where('created_at', '>=', Carbon::now()->subMinutes(5))
            ->with(['campaign', 'dcd'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get campaigns with scans
        $campaignsWithScans = Campaign::has('scans')
            ->with(['client', 'dcd'])
            ->withCount('scans')
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // Get recent earnings (last 5 minutes)
        $recentEarnings = Earning::where('created_at', '>=', Carbon::now()->subMinutes(5))
            ->with(['user', 'campaign'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Get total earnings by type
        $earningsByType = Earning::selectRaw('type, COUNT(*) as count, SUM(amount) as total')
            ->groupBy('type')
            ->get();

        // Get campaigns summary
        $campaignsSummary = [
            'total_campaigns' => Campaign::count(),
            'approved' => Campaign::where('status', 'approved')->count(),
            'live' => Campaign::where('status', 'live')->count(),
            'completed' => Campaign::where('status', 'completed')->count(),
            'total_scans' => Scan::count(),
            'total_spent' => Campaign::sum('spent_amount'),
        ];

        // Prepare data
        $data = [
            'admin' => $admin,
            'timestamp' => Carbon::now(),
            'recent_scans' => $recentScans,
            'campaigns_with_scans' => $campaignsWithScans,
            'recent_earnings' => $recentEarnings,
            'earnings_by_type' => $earningsByType,
            'campaigns_summary' => $campaignsSummary,
        ];

        // Send email
        try {
            Mail::send('emails.scan_monitoring_digest', $data, function ($message) use ($admin) {
                $message->to($admin->email)
                    ->subject('Scan Monitoring Digest - ' . Carbon::now()->format('Y-m-d H:i:s'));
            });

            $this->info("Digest sent to {$admin->email}");
            $this->info("Recent scans: {$recentScans->count()}");
            $this->info("Recent earnings: {$recentEarnings->count()}");
        } catch (\Exception $e) {
            $this->error('Failed to send digest: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
