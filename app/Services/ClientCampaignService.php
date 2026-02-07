<?php

namespace App\Services;

use App\Mail\CampaignConfirmation;
use App\Mail\DaReferralCommissionNotification;
use App\Models\Campaign;
use App\Models\Country;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ClientCampaignService
{
    protected $ventureShareService;

    protected $qrCodeService;

    protected $campaignMatchingService;

    protected $adminActionService;

    public function __construct(
        VentureShareService $ventureShareService,
        QRCodeService $qrCodeService,
        CampaignMatchingService $campaignMatchingService,
        AdminActionService $adminActionService
    ) {
        $this->ventureShareService = $ventureShareService;
        $this->qrCodeService = $qrCodeService;
        $this->campaignMatchingService = $campaignMatchingService;
        $this->adminActionService = $adminActionService;
    }

    public function submitCampaign(array $data): array
    {
        Log::info('Campaign submission started', ['email' => $data['email'], 'campaign_title' => $data['campaign_title']]);

        try {
            Log::info('Campaign submission validation passed', [
                'content_safety_preferences' => $data['content_safety_preferences'],
                'content_safety_preferences_count' => count($data['content_safety_preferences'] ?? []),
            ]);

            $client = $this->findOrCreateClient($data);

            $contentSafety = $this->determineContentSafety($data['content_safety_preferences']);

            $dcdId = $this->determineDcdId($data);

            $this->processReferral($data, $client);

            $campaign = $this->createCampaign($data, $client, $dcdId, $contentSafety);

            $this->allocateVentureShares($client, $data['budget']);

            $this->sendConfirmationEmail($campaign, $client);

            $this->notifyAdmins($campaign);

            Log::info('Campaign submitted successfully', [
                'campaign_id' => $campaign->id,
                'client_id' => $client->id,
                'email' => $client->email,
            ]);

            return [
                'message' => 'Campaign submitted successfully! Your campaign is now pending review. We will contact you soon.',
                'campaign_id' => $campaign->id,
                'client_id' => $client->id,
                'status' => 'success',
            ];

        } catch (\Exception $e) {
            Log::error('Campaign submission failed: '.$e->getMessage(), [
                'request_data' => $data,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    protected function findOrCreateClient(array $data): User
    {
        $existingUser = User::where('email', $data['email'])->first();
        $client = null;

        if ($existingUser) {
            // Update existing user
            if ($existingUser->phone !== $data['phone']) {
                $phoneInUse = User::where('phone', $data['phone'])
                    ->where('id', '!=', $existingUser->id)
                    ->exists();

                if ($phoneInUse) {
                    throw new \InvalidArgumentException('This phone number is already registered with another account.');
                }
            }

            $client = $existingUser;
            $client->update([
                'name' => $data['name'],
                'phone' => $data['phone'],
                'country' => $data['country'],
                'business_name' => $data['business_name'],
                'account_type' => $data['account_type'],
                'country_id' => $data['target_country'] ? Country::where('code', strtoupper($data['target_country']))->first()?->id : null,
                'county_id' => $data['target_county'],
                'subcounty_id' => $data['target_subcounty'],
                'ward_id' => $data['target_ward'],
            ]);
        } else {
            $existingUserByPhone = User::where('phone', $data['phone'])->first();
            if ($existingUserByPhone) {
                $client = $existingUserByPhone;
                $client->update([
                    'name' => $data['name'],
                    'country' => $data['country'],
                    'business_name' => $data['business_name'],
                    'account_type' => $data['account_type'],
                    'country_id' => $data['target_country'] ? Country::where('code', strtoupper($data['target_country']))->first()?->id : null,
                    'county_id' => $data['target_county'],
                    'subcounty_id' => $data['target_subcounty'],
                    'ward_id' => $data['target_ward'],
                ]);
            } else {
                $emailInUse = User::where('email', $data['email'])->where('role', 'client')->exists();
                if ($emailInUse) {
                    throw new \InvalidArgumentException('This email address is already registered with another client account.');
                }

                $referralCode = $this->generateReferralCode($data['referral_code'] ?? null);

                $client = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'role' => 'client',
                    'phone' => $data['phone'],
                    'country' => $data['country'],
                    'business_name' => $data['business_name'],
                    'account_type' => $data['account_type'],
                    'referral_code' => $referralCode,
                    'country_id' => $data['target_country'] ? Country::where('code', strtoupper($data['target_country']))->first()?->id : null,
                    'county_id' => $data['target_county'],
                    'subcounty_id' => $data['target_subcounty'],
                    'ward_id' => $data['target_ward'],
                    'password' => bcrypt('temporary_password_'.time()),
                ]);
            }
        }

        return $client;
    }

    protected function generateReferralCode(?string $providedCode): string
    {
        if ($providedCode) {
            $existingCode = User::where('referral_code', strtoupper($providedCode))->first();
            if (! $existingCode) {
                return strtoupper($providedCode);
            }
        }

        do {
            $referralCode = Str::upper(Str::random(8));
        } while (User::where('referral_code', $referralCode)->exists());

        return $referralCode;
    }

    protected function determineContentSafety(array $preferences): string
    {
        if (in_array('adult', $preferences) || in_array('no_restrictions', $preferences)) {
            return 'mature_audience';
        }

        return 'family_friendly';
    }

    protected function determineDcdId(array $data): ?int
    {
        if (isset($data['dcd_id']) && $data['dcd_id']) {
            $dcd = User::where('id', $data['dcd_id'])->where('role', 'dcd')->first();
            if ($dcd) {
                Log::info('Campaign assigned to DCD from QR scan', ['dcd_id' => $dcd->id, 'dcd_name' => $dcd->name]);

                return $dcd->id;
            }
        }

        return null;
    }

    protected function processReferral(array $data, User $client): void
    {
        if (isset($data['referred_by_code']) && $data['referred_by_code']) {
            $referrer = User::where('referral_code', strtoupper($data['referred_by_code']))->first();

            if ($referrer && $referrer->role === 'da') {
                \App\Models\Referral::create([
                    'referrer_id' => $referrer->id,
                    'referred_id' => $client->id,
                    'type' => 'da_to_client',
                ]);

                try {
                    Mail::to($referrer->email)->send(new DaReferralCommissionNotification($referrer, $client));
                    Log::info('DA referral commission notification sent for client signup', [
                        'referrer_id' => $referrer->id,
                        'client_id' => $client->id,
                        'referrer_email' => $referrer->email,
                        'campaign_budget' => $data['budget'],
                    ]);
                } catch (\Exception $e) {
                    Log::warning('Failed to send DA referral commission notification for client: '.$e->getMessage());
                }
            }
        }
    }

    protected function createCampaign(array $data, User $client, ?int $dcdId, string $contentSafety): Campaign
    {
        $costPerClick = $this->calculateCostPerClick($data['campaign_objective'], $data['explainer_video_url'] ?? null, $data['country']);
        $maxScans = $costPerClick > 0 ? floor($data['budget'] / $costPerClick) : 0;

        return Campaign::create([
            'client_id' => $client->id,
            'dcd_id' => $dcdId,
            'title' => $data['campaign_title'],
            'description' => $data['description'] ?? 'No description provided',
            'budget' => $data['budget'],
            'cost_per_click' => $costPerClick,
            'spent_amount' => 0,
            'campaign_credit' => 0,
            'max_scans' => $maxScans,
            'total_scans' => 0,
            'county' => $data['target_county'] ?? 'Not specified',
            'status' => 'submitted',
            'campaign_objective' => $data['campaign_objective'],
            'digital_product_link' => $data['digital_product_link'],
            'explainer_video_url' => $data['explainer_video_url'] ?? null,
            'target_audience' => $data['target_audience'] ?? 'General audience',
            'duration' => $data['start_date'].' to '.$data['end_date'],
            'objectives' => $data['objectives'] ?? 'Campaign objectives as described',
            'metadata' => [
                'digital_product_link' => $data['digital_product_link'],
                'explainer_video_url' => $data['explainer_video_url'] ?? null,
                'campaign_objective' => $data['campaign_objective'],
                'content_safety' => $contentSafety,
                'content_safety_preferences' => $data['content_safety_preferences'],
                'target_country' => $data['target_country'],
                'target_county' => $data['target_county'],
                'target_subcounty' => $data['target_subcounty'],
                'target_ward' => $data['target_ward'],
                'business_types' => $data['business_types'],
                'music_genres' => $data['music_genres'] ?? [],
                'start_date' => $data['start_date'],
                'end_date' => $data['end_date'],
                'account_type' => $data['account_type'],
                'business_name' => $data['business_name'],
                'phone' => $data['phone'],
                'country' => $data['country'],
                'referral_code' => $data['referral_code'] ?? null,
            ],
        ]);
    }

    protected function allocateVentureShares(User $client, float $budget): void
    {
        try {
            $this->ventureShareService->allocateSharesForCampaignSubmission($client, $budget);
        } catch (\Exception $e) {
            Log::warning('Failed to allocate venture shares: '.$e->getMessage());
        }
    }

    protected function sendConfirmationEmail(Campaign $campaign, User $client): void
    {
        try {
            Mail::to($client->email)->send(new CampaignConfirmation($campaign, $campaign->dcd));
        } catch (\Exception $e) {
            Log::warning('Failed to send confirmation email: '.$e->getMessage());
        }
    }

    protected function notifyAdmins(Campaign $campaign): void
    {
        try {
            $this->adminActionService->notifyAllAdminsOfPendingCampaign($campaign);
        } catch (\Exception $e) {
            Log::warning('Failed to notify admins: '.$e->getMessage());
        }
    }

    protected function calculateCostPerClick(string $objective, ?string $explainerVideo, string $countryCode): float
    {
        $baseRate = match ($objective) {
            'music_promotion' => 1.0,
            'app_downloads' => 5.0,
            'product_launch' => 5.0,
            'apartment_listing' => 5.0,
            'brand_awareness' => $explainerVideo ? 5.0 : 1.0,
            'event_promotion' => $explainerVideo ? 5.0 : 1.0,
            'social_cause' => $explainerVideo ? 5.0 : 1.0,
            default => 1.0,
        };

        if (strtoupper($countryCode) === 'NG') {
            return $baseRate * 10;
        }

        return $baseRate;
    }
}
