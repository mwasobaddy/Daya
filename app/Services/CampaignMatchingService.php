<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CampaignMatchingService
{
    /**
     * Attempt to find and assign a suitable DCD for a campaign.
     * Returns assigned User or null when no match is found.
     */
    public function assignDcd(Campaign $campaign): ?User
    {
        // If a DCD is already assigned, return it
        if ($campaign->dcd_id) {
            return User::find($campaign->dcd_id);
        }

        $metadata = $campaign->metadata ?? [];
        $businessName = $metadata['business_name'] ?? null;
        $businessTypes = $metadata['business_types'] ?? null; // array

        // Basic matching algorithm: first try exact business name match, then account type
        return DB::transaction(function () use ($campaign, $businessName, $businessTypes) {
            // Build base query for DCDs that do not have active campaigns
            $baseQuery = User::where('role', 'dcd')
                ->whereDoesntHave('assignedCampaigns', function ($q) {
                    $q->whereIn('status', ['submitted', 'approved', 'active']);
                });

            // Try business name exact match (case insensitive)
            if ($businessName) {
                $candidate = (clone $baseQuery)
                    ->whereRaw('LOWER(business_name) = ?', [strtolower($businessName)])
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->first();

                if ($candidate) {
                    $campaign->dcd_id = $candidate->id;
                    $campaign->save();
                    return $candidate;
                }
            }

            // Fallback: match by account_type (business_types array)
            if (!empty($businessTypes) && is_array($businessTypes)) {
                $candidate = (clone $baseQuery)
                    ->whereIn('account_type', $businessTypes)
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->first();

                if ($candidate) {
                    $campaign->dcd_id = $candidate->id;
                    $campaign->save();
                    return $candidate;
                }
            }

            // No match found
            return null;
        });
    }
}
