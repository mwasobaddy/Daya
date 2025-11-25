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

                $candidate = $candidateQuery->lockForUpdate()->first();
                if ($candidate) {
                    $campaign->dcd_id = $candidate->id;
                    $campaign->save();
                    return $candidate;
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
                        $campaign->dcd_id = $candidateUser->id;
                        $campaign->save();
                        return $candidateUser;
                    }
                }
            }

            // No match found
            return null;
        });
    }
}
