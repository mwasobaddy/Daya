<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\User;
use App\Services\AdminActionService;
use App\Services\CampaignMatchingService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateTestCampaign extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'campaign:create-test {--budget=1000 : Campaign budget} {--objective=music_promotion : Campaign objective} {--dcd_id= : Specific DCD ID to link to}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test campaign, approve it, and link it to a DCD';

    protected $adminActionService;

    protected $campaignMatchingService;

    public function __construct(AdminActionService $adminActionService, CampaignMatchingService $campaignMatchingService)
    {
        parent::__construct();
        $this->adminActionService = $adminActionService;
        $this->campaignMatchingService = $campaignMatchingService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $budget = (float) $this->option('budget');
        $objective = $this->option('objective');
        $dcdId = $this->option('dcd_id');

        $this->info("Creating test campaign with budget: KSh {$budget}, objective: {$objective}");

        try {
            // Get test geographical data
            $ward = \App\Models\Ward::with('subcounty.county.country')->first();
            if (! $ward) {
                $this->error('No ward found in database. Please seed the database first.');

                return;
            }

            // Create or find test client
            $clientEmail = 'test-client@example.com';
            $client = User::where('email', $clientEmail)->first();

            if (! $client) {
                $this->info('Creating test client...');
                $client = User::create([
                    'name' => 'Test Client',
                    'email' => $clientEmail,
                    'password' => bcrypt('1234'),
                    'role' => 'client',
                    'phone' => '+1'.rand(100000000, 999999999),
                    'country_id' => $ward->subcounty->county->country->id,
                    'county_id' => $ward->subcounty->county->id,
                    'subcounty_id' => $ward->subcounty->id,
                    'ward_id' => $ward->id,
                    'business_name' => 'Test Client Business',
                    'account_type' => 'business',
                    'referral_code' => Str::upper(Str::random(8)),
                ]);
                $this->info("Test client created with ID: {$client->id}");
            }

            // Calculate cost per click based on objective
            $costPerClick = $this->calculateCostPerClick($objective);
            $maxScans = floor($budget / $costPerClick);

            $this->info("Cost per click: KSh {$costPerClick}, Max scans: {$maxScans}");

            // Create campaign
            $campaign = Campaign::create([
                'client_id' => $client->id,
                'dcd_id' => $dcdId ? (int) $dcdId : null, // Will be set during approval/matching
                'title' => "Test Campaign - {$objective}",
                'description' => "Test campaign for {$objective} with budget KSh {$budget}",
                'budget' => $budget,
                'cost_per_click' => $costPerClick,
                'spent_amount' => 0,
                'campaign_credit' => 0, // Will be set to budget on approval
                'max_scans' => $maxScans,
                'total_scans' => 0,
                'county' => $ward->subcounty->county->name,
                'status' => 'submitted',
                'campaign_objective' => $objective,
                'digital_product_link' => 'https://example.com/test-campaign',
                'explainer_video_url' => null,
                'target_audience' => 'General audience',
                'duration' => now()->format('Y-m-d').' to '.now()->addDays(30)->format('Y-m-d'),
                'objectives' => "Test campaign objectives for {$objective}",
                'metadata' => [
                    'digital_product_link' => 'https://example.com/test-campaign',
                    'explainer_video_url' => null,
                    'campaign_objective' => $objective,
                    'content_safety' => 'family_friendly',
                    'content_safety_preferences' => ['safe_for_kids'],
                    'target_country' => $ward->subcounty->county->country->code,
                    'target_county' => $ward->subcounty->county->id,
                    'target_subcounty' => $ward->subcounty->id,
                    'target_ward' => $ward->id,
                    'business_types' => ['cafe'], // Match the test DCD's business type
                    'music_genres' => [],
                    'start_date' => now()->format('Y-m-d'),
                    'end_date' => now()->addDays(30)->format('Y-m-d'),
                    'account_type' => 'business',
                    'business_name' => 'Test Client Business',
                    'phone' => $client->phone,
                    'country' => $ward->subcounty->county->country->code,
                    'referral_code' => null,
                ],
            ]);

            $this->info("Campaign created with ID: {$campaign->id}, Status: {$campaign->status}");

            // Submit campaign (simulate client submission)
            $campaign->update(['status' => 'under_review']);
            $this->info('Campaign submitted for review');

            // Create admin user if doesn't exist
            $admin = User::where('role', 'admin')->first();
            if (! $admin) {
                $this->info('Creating test admin...');
                $admin = User::create([
                    'name' => 'Test Admin',
                    'email' => 'admin@example.com',
                    'password' => bcrypt('1234'),
                    'role' => 'admin',
                    'phone' => '+1234567890',
                    'country_id' => $ward->subcounty->county->country->id,
                ]);
            }

            // Approve the campaign using the admin action system
            $this->info('Approving campaign...');
            $actionLink = $this->adminActionService->generateActionLink('approve_campaign', $campaign->id);

            // Extract token from the URL path (format: /admin/action/approve_campaign/{token}?signature=...)
            $urlParts = parse_url($actionLink);
            $path = $urlParts['path'] ?? '';
            $pathParts = explode('/', $path);

            // Path should be: /admin/action/approve_campaign/{token}
            if (count($pathParts) >= 4 && $pathParts[1] === 'admin' && $pathParts[2] === 'action') {
                $action = $pathParts[3]; // approve_campaign
                $token = $pathParts[4] ?? null; // the token
            } else {
                throw new \Exception('Invalid action link path format');
            }

            if (! $token || ! $action) {
                throw new \Exception('Failed to extract token and action from link');
            }

            $result = $this->adminActionService->executeAction($token, $action);

            $campaign->refresh();
            $this->info("Campaign approved! Status: {$campaign->status}, Campaign Credit: KSh {$campaign->campaign_credit}");

            if ($campaign->dcd_id) {
                $dcd = User::find($campaign->dcd_id);
                $this->info("Campaign linked to DCD: {$dcd->name} (ID: {$dcd->id})");
            } else {
                $this->warn('No DCD was auto-matched. Campaign is approved but not linked to any DCD.');
                $this->info("You can manually link it using: php artisan tinker then \$campaign = Campaign::find({$campaign->id}); \$campaign->update(['dcd_id' => 1]);");
            }

            $this->info('Test campaign created successfully!');
            $this->info("Campaign ID: {$campaign->id}");
            $this->info("Client: {$client->name} ({$client->email})");
            $this->info("Budget: KSh {$budget}");
            $this->info("Cost per click: KSh {$costPerClick}");
            $this->info("Max scans: {$maxScans}");
            $this->info("Status: {$campaign->status}");

            if ($campaign->dcd_id) {
                $this->info("\n🎉 Campaign is ready for testing!");
                $this->info("You can now scan the DCD's QR code to test the scan per cost functionality.");
            }

        } catch (\Exception $e) {
            $this->error('Failed to create test campaign: '.$e->getMessage());
            \Log::error('Test campaign creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }

    protected function calculateCostPerClick(string $objective): float
    {
        return match ($objective) {
            'music_promotion' => 1.0,
            'app_downloads' => 5.0,
            'product_launch' => 5.0,
            'apartment_listing' => 5.0,
            'deal_listing' => 5.0,
            'brand_awareness' => 1.0,
            'event_promotion' => 1.0,
            'social_cause' => 1.0,
            default => 1.0,
        };
    }
}
