<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\User;
use App\Models\Country;
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
        $musicGenres = $metadata['music_genres'] ?? null; // array
        $targetCountryCode = $metadata['target_country'] ?? null; // country code (optional)

        // Basic matching algorithm: first try exact business name match, then account type
        return DB::transaction(function () use ($campaign, $businessName, $businessTypes, $musicGenres, $targetCountryCode) {
            // Build base query for DCDs that do not have active campaigns
            // We'll check for date overlaps separately in PHP to support SQLite
            $baseQuery = User::where('role', 'dcd')
                ->whereDoesntHave('assignedCampaigns', function ($q) {
                    $q->whereIn('status', ['submitted', 'approved', 'active']);
                });

            // Try business name exact match (case insensitive)
            if ($businessName) {
                $candidates = (clone $baseQuery)
                    ->whereRaw('LOWER(business_name) = ?', [strtolower($businessName)])
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($candidates as $candidate) {
                    if (!$this->hasDateOverlap($candidate, $campaign)) {
                        $campaign->dcd_id = $candidate->id;
                        $campaign->save();
                        return $candidate;
                    }
                }
            }

            // Fallback: match by account_type (business_types array)
            if (!empty($businessTypes) && is_array($businessTypes)) {
                $candidates = (clone $baseQuery)
                    ->whereIn('account_type', $businessTypes)
                    ->orderBy('created_at', 'asc')
                    ->lockForUpdate()
                    ->get();

                foreach ($candidates as $candidate) {
                    if (!$this->hasDateOverlap($candidate, $campaign)) {
                        $campaign->dcd_id = $candidate->id;
                        $campaign->save();
                        return $candidate;
                    }
                }
            }

            // Next fallback: match by music genres (if provided)
            if (!empty($musicGenres) && is_array($musicGenres)) {
                // Try DB-powered JSON query first (efficient on MySQL/Postgres)
                $candidateQuery = (clone $baseQuery)->orderBy('created_at', 'asc');
                $candidateQuery->where(function ($q) use ($musicGenres) {
                    foreach ($musicGenres as $g) {
                        $q->orWhereJsonContains('profile->music_genres', $g);
                    }
                });

                // Prefer same target country if provided
                if ($targetCountryCode) {
                    $country = Country::where('code', strtoupper($targetCountryCode))->first();
                    if ($country) {
                        $candidateQuery->where('country_id', $country->id);
                    }
                }

                $candidates = $candidateQuery->lockForUpdate()->get();
                foreach ($candidates as $candidate) {
                    if (!$this->hasDateOverlap($candidate, $campaign)) {
                        $campaign->dcd_id = $candidate->id;
                        $campaign->save();
                        return $candidate;
                    }
                }

                // Fallback: query a smaller candidate set (possibly country-filtered) and do PHP-level matching to support SQLite / other DBs.
                $inMemoryQuery = clone $baseQuery;
                if ($targetCountryCode && isset($country)) {
                    $inMemoryQuery->where('country_id', $country->id);
                }
                $candidates = $inMemoryQuery->orderBy('created_at', 'asc')->get();
                foreach ($candidates as $candidateUser) {
                    $profile = is_string($candidateUser->profile) ? json_decode($candidateUser->profile, true) : (array) $candidateUser->profile;
                    $dcdGenres = $profile['music_genres'] ?? [];
                    if (!empty($dcdGenres) && array_intersect($musicGenres, $dcdGenres)) {
                        if (!$this->hasDateOverlap($candidateUser, $campaign)) {
                            $campaign->dcd_id = $candidateUser->id;
                            $campaign->save();
                            return $candidateUser;
                        }
                    }
                }
            }

            // No match found
            return null;
        });
    }

    /**
     * Check if a DCD has any campaigns that overlap with the new campaign's date range
     */
    private function hasDateOverlap(User $dcd, Campaign $newCampaign): bool
    {
        $newMetadata = $newCampaign->metadata ?? [];
        $newStartDate = $newMetadata['start_date'] ?? null;
        $newEndDate = $newMetadata['end_date'] ?? null;

        if (!$newStartDate || !$newEndDate) {
            return false; // No dates to compare
        }

        try {
            $newStart = \Carbon\Carbon::parse($newStartDate);
            $newEnd = \Carbon\Carbon::parse($newEndDate);
        } catch (\Exception $e) {
            return false; // Invalid date format, skip overlap check
        }

        // Get all active campaigns for this DCD
        $existingCampaigns = Campaign::where('dcd_id', $dcd->id)
            ->whereIn('status', ['submitted', 'approved', 'active'])
            ->get();

        foreach ($existingCampaigns as $existingCampaign) {
            $existingMetadata = $existingCampaign->metadata ?? [];
            $existingStartDate = $existingMetadata['start_date'] ?? null;
            $existingEndDate = $existingMetadata['end_date'] ?? null;

            if (!$existingStartDate || !$existingEndDate) {
                continue; // Skip campaigns without proper dates
            }

            try {
                $existingStart = \Carbon\Carbon::parse($existingStartDate);
                $existingEnd = \Carbon\Carbon::parse($existingEndDate);

                // Check for overlap: new campaign overlaps if it starts before existing ends and ends after existing starts
                if ($newStart->lte($existingEnd) && $newEnd->gte($existingStart)) {
                    return true; // Overlap found
                }
            } catch (\Exception $e) {
                continue; // Skip campaigns with invalid dates
            }
        }

        return false; // No overlaps found
    }
}
