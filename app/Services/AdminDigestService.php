<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Scan;
use App\Models\Earning;
use App\Models\User;
use App\Models\Referral;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDigestService
{
    /**
     * Get comprehensive daily digest data
     */
    public function getDailyDigestData(?Carbon $date = null): array
    {
        $date = $date ?? Carbon::yesterday();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        return [
            'date' => $date->format('M d, Y'),
            'day_name' => $date->format('l'),
            'campaigns' => $this->getCampaignMetrics($startOfDay, $endOfDay),
            'scans' => $this->getScanMetrics($startOfDay, $endOfDay),
            'users' => $this->getUserMetrics($startOfDay, $endOfDay),
            'financial' => $this->getFinancialMetrics($startOfDay, $endOfDay),
            'top_performers' => $this->getTopPerformers($startOfDay, $endOfDay),
            'alerts' => $this->getAlerts(),
            'comparison' => $this->getComparisonMetrics($startOfDay, $endOfDay),
        ];
    }

    /**
     * Get campaign metrics
     */
    protected function getCampaignMetrics(Carbon $start, Carbon $end): array
    {
        $newCampaigns = Campaign::whereBetween('created_at', [$start, $end])->get();
        $approvedCampaigns = Campaign::where('status', 'approved')
            ->whereBetween('updated_at', [$start, $end])
            ->count();
        $rejectedCampaigns = Campaign::where('status', 'rejected')
            ->whereBetween('updated_at', [$start, $end])
            ->count();
        $completedCampaigns = Campaign::where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->get();

        $activeCampaigns = Campaign::where('status', 'approved')->count();
        $pendingReview = Campaign::where('status', 'submitted')->count();

        // Campaigns nearing completion (>80% budget spent)
        $nearingCompletion = Campaign::where('status', 'approved')
            ->whereRaw('spent_amount >= (budget * 0.8)')
            ->where('spent_amount', '<', DB::raw('budget'))
            ->get()
            ->map(function ($campaign) {
                $progress = $campaign->budget > 0 
                    ? round(($campaign->spent_amount / $campaign->budget) * 100, 1)
                    : 0;
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'progress' => $progress,
                    'spent' => $campaign->spent_amount,
                    'budget' => $campaign->budget,
                    'remaining_scans' => $campaign->getRemainingScans(),
                ];
            });

        return [
            'new_count' => $newCampaigns->count(),
            'new_campaigns' => $newCampaigns->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'budget' => $c->budget,
                'objective' => $c->campaign_objective,
                'client' => $c->client->name ?? 'Unknown',
            ]),
            'approved' => $approvedCampaigns,
            'rejected' => $rejectedCampaigns,
            'completed_count' => $completedCampaigns->count(),
            'completed_campaigns' => $completedCampaigns->map(fn($c) => [
                'id' => $c->id,
                'title' => $c->title,
                'total_scans' => $c->total_scans,
                'spent' => $c->spent_amount,
            ]),
            'active' => $activeCampaigns,
            'pending_review' => $pendingReview,
            'total_budget_new' => $newCampaigns->sum('budget'),
            'nearing_completion' => $nearingCompletion,
        ];
    }

    /**
     * Get scan metrics
     */
    protected function getScanMetrics(Carbon $start, Carbon $end): array
    {
        $scans = Scan::whereBetween('scanned_at', [$start, $end]);
        $totalScans = $scans->count();
        
        $scansWithEarnings = Scan::whereBetween('scanned_at', [$start, $end])
            ->where('earnings', '>', 0)
            ->count();

        $totalEarnings = Scan::whereBetween('scanned_at', [$start, $end])
            ->sum('earnings');

        // Failed scans (scans without earnings)
        $failedScans = $totalScans - $scansWithEarnings;

        // Estimate duplicates prevented (scans with same fingerprint within timeframe)
        $duplicatesPrevented = Scan::select('device_fingerprint')
            ->whereBetween('scanned_at', [$start, $end])
            ->whereNotNull('device_fingerprint')
            ->where('earnings', 0)
            ->count();

        // Average scans per campaign
        $avgScansPerCampaign = Campaign::where('status', 'approved')
            ->withCount(['scans' => function ($query) use ($start, $end) {
                $query->whereBetween('scanned_at', [$start, $end]);
            }])
            ->get()
            ->avg('scans_count');

        return [
            'total' => $totalScans,
            'successful' => $scansWithEarnings,
            'failed' => $failedScans,
            'duplicates_prevented' => $duplicatesPrevented,
            'total_earnings' => $totalEarnings,
            'avg_per_campaign' => round($avgScansPerCampaign ?? 0, 1),
        ];
    }

    /**
     * Get user metrics
     */
    protected function getUserMetrics(Carbon $start, Carbon $end): array
    {
        $newDAs = User::where('role', 'da')
            ->whereBetween('created_at', [$start, $end])
            ->get();
        
        $newDCDs = User::where('role', 'dcd')
            ->whereBetween('created_at', [$start, $end])
            ->get();
        
        $newClients = User::where('role', 'client')
            ->whereBetween('created_at', [$start, $end])
            ->get();

        $newReferrals = Referral::whereBetween('created_at', [$start, $end])->count();

        return [
            'new_das_count' => $newDAs->count(),
            'new_das' => $newDAs->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]),
            'new_dcds_count' => $newDCDs->count(),
            'new_dcds' => $newDCDs->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'business_name' => $u->business_name ?? 'N/A',
            ]),
            'new_clients_count' => $newClients->count(),
            'new_clients' => $newClients->map(fn($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
            ]),
            'new_referrals' => $newReferrals,
            'total_active_users' => [
                'das' => User::where('role', 'da')->count(),
                'dcds' => User::where('role', 'dcd')->count(),
                'clients' => User::where('role', 'client')->count(),
            ],
        ];
    }

    /**
     * Get financial metrics
     */
    protected function getFinancialMetrics(Carbon $start, Carbon $end): array
    {
        $revenueToday = Campaign::whereBetween('created_at', [$start, $end])
            ->sum('budget');

        $pendingEarnings = Earning::where('status', 'pending')
            ->sum('amount');

        $paidEarnings = Earning::where('status', 'paid')
            ->whereBetween('updated_at', [$start, $end])
            ->sum('amount');

        // Budget utilization across all active campaigns
        $activeCampaigns = Campaign::where('status', 'approved')->get();
        $totalBudgetAllocated = $activeCampaigns->sum('budget');
        $totalBudgetSpent = $activeCampaigns->sum('spent_amount');
        $utilizationRate = $totalBudgetAllocated > 0 
            ? round(($totalBudgetSpent / $totalBudgetAllocated) * 100, 1)
            : 0;

        return [
            'revenue_today' => $revenueToday,
            'pending_earnings' => $pendingEarnings,
            'paid_today' => $paidEarnings,
            'budget_utilization_rate' => $utilizationRate,
            'total_budget_allocated' => $totalBudgetAllocated,
            'total_budget_spent' => $totalBudgetSpent,
        ];
    }

    /**
     * Get top performers
     */
    protected function getTopPerformers(Carbon $start, Carbon $end): array
    {
        // Top DCD by scans
        $topDCD = Scan::select('dcd_id', DB::raw('COUNT(*) as scan_count'), DB::raw('SUM(earnings) as total_earnings'))
            ->whereBetween('scanned_at', [$start, $end])
            ->groupBy('dcd_id')
            ->orderBy('scan_count', 'desc')
            ->with('dcd')
            ->first();

        // Top campaign by scans
        $topCampaign = Scan::select('campaign_id', DB::raw('COUNT(*) as scan_count'))
            ->whereBetween('scanned_at', [$start, $end])
            ->groupBy('campaign_id')
            ->orderBy('scan_count', 'desc')
            ->with('campaign')
            ->first();

        // Top referrer (DA with most referrals)
        $topReferrer = Referral::select('referrer_id', DB::raw('COUNT(*) as referral_count'))
            ->where('type', 'da_to_client')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('referrer_id')
            ->orderBy('referral_count', 'desc')
            ->with('referrer')
            ->first();

        return [
            'top_dcd' => $topDCD ? [
                'name' => $topDCD->dcd->name ?? 'Unknown',
                'business_name' => $topDCD->dcd->business_name ?? 'N/A',
                'scans' => $topDCD->scan_count,
                'earnings' => $topDCD->total_earnings,
            ] : null,
            'top_campaign' => $topCampaign ? [
                'id' => $topCampaign->campaign->id ?? 0,
                'title' => $topCampaign->campaign->title ?? 'Unknown',
                'scans' => $topCampaign->scan_count,
            ] : null,
            'top_referrer' => $topReferrer ? [
                'name' => $topReferrer->referrer->name ?? 'Unknown',
                'referrals' => $topReferrer->referral_count,
            ] : null,
        ];
    }

    /**
     * Get alerts and items requiring attention
     */
    protected function getAlerts(): array
    {
        $budgetWarnings = Campaign::where('status', 'approved')
            ->whereRaw('spent_amount >= (budget * 0.9)')
            ->where('spent_amount', '<', DB::raw('budget'))
            ->count();

        $pendingApprovals = Campaign::where('status', 'submitted')->count();

        $inactiveDCDs = User::where('role', 'dcd')
            ->whereDoesntHave('scans', function ($query) {
                $query->where('scanned_at', '>=', Carbon::now()->subDays(3));
            })
            ->count();

        // System errors in last 24 hours (we'll check logs or a custom error tracking table if exists)
        $systemErrors = 0; // Placeholder - implement based on your error tracking

        return [
            'budget_warnings' => $budgetWarnings,
            'pending_approvals' => $pendingApprovals,
            'inactive_dcds' => $inactiveDCDs,
            'system_errors' => $systemErrors,
        ];
    }

    /**
     * Get comparison metrics (today vs average)
     */
    protected function getComparisonMetrics(Carbon $start, Carbon $end): array
    {
        // Get metrics for today
        $todayScans = Scan::whereBetween('scanned_at', [$start, $end])->count();
        $todayEarnings = Scan::whereBetween('scanned_at', [$start, $end])->sum('earnings');
        $todayCampaigns = Campaign::whereBetween('created_at', [$start, $end])->count();

        // Get average for last 7 days (excluding today)
        $sevenDaysAgo = $start->copy()->subDays(7);
        $avgScans = Scan::whereBetween('scanned_at', [$sevenDaysAgo, $start])
            ->count() / 7;
        $avgEarnings = Scan::whereBetween('scanned_at', [$sevenDaysAgo, $start])
            ->sum('earnings') / 7;
        $avgCampaigns = Campaign::whereBetween('created_at', [$sevenDaysAgo, $start])
            ->count() / 7;

        return [
            'scans' => [
                'today' => $todayScans,
                'average' => round($avgScans, 1),
                'change_percent' => $avgScans > 0 ? round((($todayScans - $avgScans) / $avgScans) * 100, 1) : 0,
            ],
            'earnings' => [
                'today' => $todayEarnings,
                'average' => round($avgEarnings, 2),
                'change_percent' => $avgEarnings > 0 ? round((($todayEarnings - $avgEarnings) / $avgEarnings) * 100, 1) : 0,
            ],
            'campaigns' => [
                'today' => $todayCampaigns,
                'average' => round($avgCampaigns, 1),
                'change_percent' => $avgCampaigns > 0 ? round((($todayCampaigns - $avgCampaigns) / $avgCampaigns) * 100, 1) : 0,
            ],
        ];
    }
}
