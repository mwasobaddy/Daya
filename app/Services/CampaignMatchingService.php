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
            // Get campaign date range from metadata
            $campaignMetadata = $campaign->metadata ?? [];
            $startDate = $campaignMetadata['start_date'] ?? null;
            $endDate = $campaignMetadata['end_date'] ?? null;
            
            // Build base query for DCDs with less than 3 active campaigns and no date overlaps
            $baseQuery = User::where('role', 'dcd')
                ->whereHas('assignedCampaigns', function ($q) {
                    $q->where('status', 'live')
                      ->whereRaw("JSON_EXTRACT(metadata, '$.start_date') <= ?", [now()->format('Y-m-d')])
                      ->whereRaw("JSON_EXTRACT(metadata, '$.end_date') >= ?", [now()->format('Y-m-d')]);
                }, '<', 3) // Max 3 active campaigns per DCD
                ->whereDoesntHave('assignedCampaigns', function ($q) use ($startDate, $endDate) {
                    // Exclude DCDs with campaigns that overlap with the new campaign dates
                    $q->whereIn('status', ['approved', 'live'])
                      ->where(function ($overlapQuery) use ($startDate, $endDate) {
                          if ($startDate && $endDate) {
                              $overlapQuery->whereRaw("JSON_EXTRACT(metadata, '$.start_date') <= ?", [$endDate])
                                          ->whereRaw("JSON_EXTRACT(metadata, '$.end_date') >= ?", [$startDate]);
                          }
                      });
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

            // Fallback: match by business_types array in DCD profile
            if (!empty($businessTypes) && is_array($businessTypes)) {
                // Try DB-powered JSON query first (efficient on MySQL/Postgres)
                $candidateQuery = (clone $baseQuery)->orderBy('created_at', 'asc');
                $candidateQuery->where(function ($q) use ($businessTypes) {
                    foreach ($businessTypes as $bt) {
                        $q->orWhereJsonContains('profile->business_types', $bt);
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
                    $dcdBusinessTypes = $profile['business_types'] ?? [];
                    if (!empty($dcdBusinessTypes) && array_intersect($businessTypes, $dcdBusinessTypes)) {
                        $campaign->dcd_id = $candidateUser->id;
                        $campaign->save();
                        return $candidateUser;
                    }
                }
            }

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

            // Strategy 4: Match by campaign objective against DCD campaign_types
            // This is a fallback when no specific genre matching is possible
            if ($campaign->campaign_objective && $campaign->campaign_objective !== '-') {
                // Map campaign objectives to DCD campaign types
                $objectiveToTypeMap = [
                    'app_downloads' => 'mobile_apps',
                    'music_promotion' => 'music',
                    'product_launch' => 'product_launch', 
                    'apartment_listing' => 'apartment_listing',
                    'event_promotion' => 'events',
                    'brand_awareness' => ['movies', 'games', 'mobile_apps'], // Brand awareness can work with multiple content types
                    'social_cause' => 'education', // Social causes often educational
                ];

                $targetCampaignTypes = $objectiveToTypeMap[$campaign->campaign_objective] ?? null;
                
                if ($targetCampaignTypes) {
                    // Ensure it's an array for consistent processing
                    if (!is_array($targetCampaignTypes)) {
                        $targetCampaignTypes = [$targetCampaignTypes];
                    }

                    // Get DCDs and check their campaign_types in profile
                    $candidates = (clone $baseQuery)->orderBy('created_at', 'asc')->get();
                    
                    foreach ($candidates as $candidateUser) {
                        $profile = is_string($candidateUser->profile) ? 
                            json_decode($candidateUser->profile, true) : (array) $candidateUser->profile;
                        $dcdCampaignTypes = $profile['campaign_types'] ?? [];
                        
                        // Check if DCD's campaign types match any of the target types
                        if (!empty($dcdCampaignTypes) && array_intersect($targetCampaignTypes, $dcdCampaignTypes)) {
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
}
