<?php

namespace App\Services;

use App\Models\User;
use App\Models\Campaign;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DCDCampaignSelectionService
{
    /**
     * Find the best active campaign for a DCD at the current moment.
     * Logic:
     * 1. Get all approved campaigns assigned to this DCD
     * 2. Filter out expired campaigns
     * 3. Filter campaigns active today (within start_date and end_date range)
     * 4. Return oldest campaign first (by created_at)
     */
    public function getActiveCampaignForDCD(User $dcd): ?Campaign
    {
        if ($dcd->role !== 'dcd') {
            Log::warning('DCDCampaignSelectionService: User is not a DCD', ['user_id' => $dcd->id]);
            return null;
        }

        // Get all approved campaigns for this DCD
        $campaigns = Campaign::where('dcd_id', $dcd->id)
            ->where('status', 'approved')
            ->orderBy('created_at', 'asc') // Prioritize oldest first
            ->get();

        $today = Carbon::today();
        
        foreach ($campaigns as $campaign) {
            if ($this->isCampaignActiveToday($campaign, $today)) {
                return $campaign;
            }
        }

        return null;
    }

    /**
     * Check if a campaign is active today based on metadata start_date and end_date
     */
    private function isCampaignActiveToday(Campaign $campaign, Carbon $today): bool
    {
        $metadata = $campaign->metadata ?? [];
        
        $startDateStr = $metadata['start_date'] ?? null;
        $endDateStr = $metadata['end_date'] ?? null;

        if (!$startDateStr || !$endDateStr) {
            Log::warning('DCDCampaignSelectionService: Campaign missing start/end dates', [
                'campaign_id' => $campaign->id,
                'start_date' => $startDateStr,
                'end_date' => $endDateStr
            ]);
            return false;
        }

        try {
            $startDate = Carbon::parse($startDateStr);
            $endDate = Carbon::parse($endDateStr);

            // Check if today is within the campaign date range (inclusive)
            return $today->between($startDate, $endDate);
        } catch (\Exception $e) {
            Log::warning('DCDCampaignSelectionService: Invalid date format in campaign metadata', [
                'campaign_id' => $campaign->id,
                'start_date' => $startDateStr,
                'end_date' => $endDateStr,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get a user-friendly message when no active campaign is found
     */
    public function getNoActiveCampaignMessage(): string
    {
        return "No active campaigns right now, try again later";
    }
}