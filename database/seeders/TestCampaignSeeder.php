<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Campaign;
use App\Models\Referral;
use App\Services\QRCodeService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestCampaignSeeder extends Seeder
{
    /**
     * 12 Airbnb locations across Nairobi for apartment_listing campaigns.
     * Coordinates sourced from Airbnb Map with Coordinates.xlsx.
     */
    private array $airbnbLocationCampaigns = [
        [
            'title' => 'Westlands Luxury Apartment',
            'area' => 'Westlands',
            'listing_id' => 42443532,
            'latitude' => -1.2663,
            'longitude' => 36.8061,
            'budget' => 2000,
            'spent_base' => 0,
            'max_scans' => 400,
            'total_scans_base' => 0,
            'end_day' => 31,
        ],
        [
            'title' => 'CBD Studio Apartment',
            'area' => 'CBD / Nairobi Central',
            'listing_id' => 44430432,
            'latitude' => -1.2767,
            'longitude' => 36.8181,
            'budget' => 2500,
            'spent_base' => 0,
            'max_scans' => 500,
            'total_scans_base' => 0,
            'end_day' => 30,
        ],
        [
            'title' => 'Kilimani 1-Bedroom Unit',
            'area' => 'Kilimani',
            'listing_id' => 18641555,
            'latitude' => -1.2907,
            'longitude' => 36.7805,
            'budget' => 1500,
            'spent_base' => 50,
            'max_scans' => 300,
            'total_scans_base' => 10,
            'end_day' => 28,
        ],
        [
            'title' => 'Karen Executive Home',
            'area' => 'Karen',
            'listing_id' => 1808465,
            'latitude' => -1.3233,
            'longitude' => 36.7077,
            'budget' => 3000,
            'spent_base' => 0,
            'max_scans' => 600,
            'total_scans_base' => 0,
            'end_day' => 25,
        ],
        [
            'title' => 'Langata Family House',
            'area' => 'Langata',
            'listing_id' => 2486516,
            'latitude' => -1.3734,
            'longitude' => 36.7403,
            'budget' => 1800,
            'spent_base' => 20,
            'max_scans' => 360,
            'total_scans_base' => 4,
            'end_day' => 20,
        ],
        [
            'title' => 'Kileleshwa Modern Flat',
            'area' => 'Kileleshwa',
            'listing_id' => 44079289,
            'latitude' => -1.2999,
            'longitude' => 36.7887,
            'budget' => 2200,
            'spent_base' => 0,
            'max_scans' => 440,
            'total_scans_base' => 0,
            'end_day' => 15,
        ],
        [
            'title' => 'Gigiri Townhouse',
            'area' => 'Gigiri',
            'listing_id' => 2868580,
            'latitude' => -1.2298,
            'longitude' => 36.7976,
            'budget' => 2800,
            'spent_base' => 100,
            'max_scans' => 560,
            'total_scans_base' => 20,
            'end_day' => 10,
        ],
        [
            'title' => 'Kasarani Affordable Unit',
            'area' => 'Kasarani',
            'listing_id' => 25347851,
            'latitude' => -1.2158,
            'longitude' => 36.8995,
            'budget' => 1200,
            'spent_base' => 0,
            'max_scans' => 240,
            'total_scans_base' => 0,
            'end_day' => 5,
        ],
        [
            'title' => 'Upper Hill Panoramic View',
            'area' => 'Upper Hill',
            'listing_id' => 44234065,
            'latitude' => -1.2891,
            'longitude' => 36.7981,
            'budget' => 2600,
            'spent_base' => 30,
            'max_scans' => 520,
            'total_scans_base' => 6,
            'end_day' => 31,
        ],
        [
            'title' => 'Embakasi Starter Home',
            'area' => 'Embakasi',
            'listing_id' => 22872353,
            'latitude' => -1.2840,
            'longitude' => 36.8715,
            'budget' => 1400,
            'spent_base' => 0,
            'max_scans' => 280,
            'total_scans_base' => 0,
            'end_day' => 26,
        ],
        [
            'title' => 'Hurlingham Serviced Apt',
            'area' => 'Hurlingham',
            'listing_id' => 43418403,
            'latitude' => -1.2790,
            'longitude' => 36.7915,
            'budget' => 2300,
            'spent_base' => 80,
            'max_scans' => 460,
            'total_scans_base' => 16,
            'end_day' => 22,
        ],
        [
            'title' => 'Parklands Studio',
            'area' => 'Parklands',
            'listing_id' => 37707710,
            'latitude' => -1.2702,
            'longitude' => 36.8000,
            'budget' => 1700,
            'spent_base' => 0,
            'max_scans' => 340,
            'total_scans_base' => 0,
            'end_day' => 18,
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating test data for manual earnings testing...');

        // 1. Create a DA user (if not exists)
        $da = User::where('email', 'test.da@example.com')->first();
        if (!$da) {
            $da = User::create([
                'name' => 'Test DA',
                'email' => 'test.da@example.com',
                'password' => Hash::make('password123'),
                'role' => 'da',
                'phone' => '+254****0001',
                'national_id' => 'DA999999999',
                'ward_id' => 1,
                'wallet_pin' => Hash::make('1234'),
                'wallet_type' => 'personal',
                'wallet_status' => 'active',
                'referral_code' => 'TESTDA01',
                'email_verified_at' => now(),
                'profile' => [
                    'full_name' => 'Test Digital Ambassador',
                    'date_of_birth' => '1990-01-01',
                    'gender' => 'male',
                    'physical_address' => 'Test Address, Nairobi',
                    'interests' => ['music', 'apps', 'events'],
                    'device_type' => 'smartphone',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                ]
            ]);
            $this->command->info('Created DA: ' . $da->name . ' (' . $da->email . ')');
        }

        // 2. Create a DCD user referred by the DA
        $dcd = User::where('email', 'test.dcd@example.com')->first();
        if (!$dcd) {
            $dcd = User::create([
                'name' => 'Test DCD',
                'email' => 'test.dcd@example.com',
                'password' => Hash::make('password123'),
                'role' => 'dcd',
                'phone' => '+254****0002',
                'national_id' => 'DCD999999999',
                'ward_id' => 1,
                'wallet_pin' => Hash::make('1234'),
                'wallet_type' => 'personal',
                'wallet_status' => 'active',
                'referral_code' => 'TESTDCD01',
                'email_verified_at' => now(),
                'profile' => [
                    'full_name' => 'Test Digital Content Distributor',
                    'date_of_birth' => '1995-01-01',
                    'gender' => 'female',
                    'physical_address' => 'Test Location, Nairobi',
                    'interests' => ['music', 'social_cause', 'brand_awareness'],
                    'device_type' => 'smartphone',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                ]
            ]);
            $this->command->info('Created DCD: ' . $dcd->name . ' (' . $dcd->email . ')');

            // Create referral relationship: DA -> DCD
            Referral::create([
                'referrer_id' => $da->id,
                'referred_id' => $dcd->id,
                'type' => 'da_to_dcd',
            ]);
            $this->command->info('Created referral: DA -> DCD');
        }

        // 3. Create a client user
        $client = User::where('email', 'test.client@example.com')->first();
        if (!$client) {
            $client = User::create([
                'name' => 'Test Client',
                'email' => 'test.client@example.com',
                'password' => Hash::make('password123'),
                'role' => 'client',
                'phone' => '+254****0003',
                'ward_id' => 1,
                'wallet_pin' => Hash::make('1234'),
                'wallet_type' => 'business',
                'wallet_status' => 'active',
                'referral_code' => 'TESTCLIENT01',
                'email_verified_at' => now(),
                'profile' => [
                    'company_name' => 'Test Company Ltd',
                    'business_type' => 'Technology',
                    'contact_person' => 'Test Client',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                ]
            ]);
            $this->command->info('Created Client: ' . $client->name . ' (' . $client->email . ')');
        }

        // 4. Create non-location campaigns (app_downloads) for fairness fallback testing
        $this->createNonLocationCampaigns($client, $dcd);

        // 5. Create location-based campaigns (apartment_listing) from Airbnb data
        $this->createLocationCampaigns($client, $dcd);

        // 6. Generate QR code for the DCD
        if (!$dcd->qr_code) {
            try {
                $qrCodeService = app(QRCodeService::class);
                $qrCodeFilename = $qrCodeService->generateDcdQr($dcd);
                $dcd->update(['qr_code' => $qrCodeFilename]);
                $this->command->info('Generated QR code for DCD: ' . $dcd->name);
                $this->command->info('QR code stored at: storage/app/public/qr-codes/' . $qrCodeFilename);
            } catch (\Exception $e) {
                $this->command->error('Could not generate QR code for DCD: ' . $e->getMessage());
            }
        } else {
            $this->command->info('DCD already has QR code: ' . $dcd->qr_code);
        }

        $this->command->info('');
        $this->command->info('=== TEST DATA CREATED SUCCESSFULLY ===');
        $this->command->info('');
        $this->command->info('DA Account:');
        $this->command->info('  Email: test.da@example.com');
        $this->command->info('  Password: password123');
        $this->command->info('');
        $this->command->info('DCD Account:');
        $this->command->info('  Email: test.dcd@example.com');
        $this->command->info('  Password: password123');
        $this->command->info('  QR Code: ' . ($dcd->qr_code ? 'storage/app/public/qr-codes/' . $dcd->qr_code : 'Not generated'));
        $this->command->info('');
        $this->command->info('Client Account:');
        $this->command->info('  Email: test.client@example.com');
        $this->command->info('  Password: password123');
        $this->command->info('');
        $this->command->info('Campaigns:');
        $this->command->info('  Total: ' . $this->getTotalCampaignCount($dcd) . ' campaigns');
        $this->command->info('    3 x app_downloads (non-location — fairness fallback)');
        $this->command->info('    ' . count($this->airbnbLocationCampaigns) . ' x apartment_listing (location-based — GPS matching)');
        $this->command->info('');
        $this->command->info('GPS Matching Test:');
        $this->command->info('  Passenger at Westlands (-1.268, 36.808) → Westlands Luxury Apartment (nearest)');
        $this->command->info('  Passenger at Karen (-1.323, 36.708)   → Karen Executive Home (nearest)');
        $this->command->info('  Passenger at CBD (-1.277, 36.818)    → CBD Studio Apartment (nearest)');
        $this->command->info('  No GPS data → fairness algorithm picks from app_downloads campaigns');
        $this->command->info('');
        $this->command->info('Expected earnings per scan:');
        $this->command->info('  - DCD: KSH 3.00 (60%)');
        $this->command->info('  - Company (Daya): KSH 1.50 (30%)');
        $this->command->info('  - DA (Referrer): KSH 0.50 (10%)');
        $this->command->info('');
        $this->command->info('To test: Scan the DCD\'s QR code and verify the correct nearest campaign is matched.');
    }

    /**
     * Create 3 non-location app_downloads campaigns (existing behavior).
     */
    private function createNonLocationCampaigns(User $client, User $dcd): void
    {
        $this->command->info('');
        $this->command->info('--- Non-Location Campaigns (Fairness Fallback) ---');

        $titles = [
            'Test App Download Campaign 1',
            'Test App Download Campaign 2',
            'Test App Download Campaign 3',
        ];

        foreach ($titles as $index => $title) {
            $campaign = Campaign::where('title', $title)->first();
            if (!$campaign) {
                $campaign = Campaign::create([
                    'client_id' => $client->id,
                    'dcd_id' => $dcd->id,
                    'title' => $title,
                    'budget' => 1000 + ($index * 100), // Vary budget: 1000, 1100, 1200
                    'cost_per_click' => 5,
                    'spent_amount' => $index * 50, // Vary spent: 0, 50, 100
                    'campaign_credit' => 1000 + ($index * 100),
                    'max_scans' => 200 - ($index * 20), // Vary max_scans: 200, 180, 160
                    'total_scans' => $index * 10, // Vary scans: 0, 10, 20
                    'county' => 'Nairobi',
                    'status' => 'live',
                    'campaign_objective' => 'app_downloads',
                    'target_audience' => 'Young adults 18-35',
                    'duration' => '2026-01-01 to 2026-12-31',
                    'objectives' => 'Test the GPS-based campaign matching system with fairness fallback',
                    'digital_product_link' => 'https://example.com/app-download/' . Str::slug($title),
                    'explainer_video_url' => 'https://example.com/explainer-video',
                    'metadata' => [
                        'start_date' => '2026-01-01',
                        'end_date' => '2026-12-' . (31 - $index * 5), // Vary end dates: 31, 26, 21
                        'target_regions' => ['Nairobi CBD', 'Westlands'],
                        'expected_conversion_rate' => '15%',
                        // NOTE: No location_latitude/location_longitude — these are non-location campaigns
                    ],
                    'completed_at' => null,
                ]);
                $this->command->info('Created: ' . $campaign->title . ' (non-location, fairness fallback)');
            }
        }
    }

    /**
     * Create location-based apartment_listing campaigns from Airbnb coordinates.
     */
    private function createLocationCampaigns(User $client, User $dcd): void
    {
        $this->command->info('');
        $this->command->info('--- Location-Based Campaigns (GPS Matching) ---');

        foreach ($this->airbnbLocationCampaigns as $data) {
            $campaign = Campaign::where('title', $data['title'])->first();
            if (!$campaign) {
                $campaign = Campaign::create([
                    'client_id' => $client->id,
                    'dcd_id' => $dcd->id,
                    'title' => $data['title'],
                    'description' => $data['title'] . ' — ' . $data['area'] . ' area, Nairobi. Airbnb listing #' . $data['listing_id'],
                    'budget' => $data['budget'] * 1.00,
                    'cost_per_click' => 5,
                    'spent_amount' => $data['spent_base'],
                    'campaign_credit' => $data['budget'] * 1.00,
                    'max_scans' => $data['max_scans'],
                    'total_scans' => $data['total_scans_base'],
                    'county' => 'Nairobi',
                    'status' => 'live',
                    'campaign_objective' => 'apartment_listing',
                    'target_audience' => 'House hunters in ' . $data['area'],
                    'duration' => '2026-01-01 to 2026-12-31',
                    'objectives' => 'Test GPS-based nearest-campaign matching with real Airbnb coordinates',
                    'digital_product_link' => 'https://www.airbnb.com/rooms/' . $data['listing_id'],
                    'explainer_video_url' => null,
                    'metadata' => [
                        'start_date' => '2026-01-01',
                        'end_date' => '2026-12-' . $data['end_day'],
                        'target_regions' => [$data['area']],
                        'location_latitude' => (string) $data['latitude'],
                        'location_longitude' => (string) $data['longitude'],
                        'listing_id' => $data['listing_id'],
                        'expected_conversion_rate' => '12%',
                    ],
                    'completed_at' => null,
                ]);
                $this->command->info('Created: ' . $campaign->title . ' (GPS: ' . $data['latitude'] . ', ' . $data['longitude'] . ')');
            }
        }
    }

    /**
     * Count total campaigns for the DCD for display purposes.
     */
    private function getTotalCampaignCount(User $dcd): int
    {
        return Campaign::where('dcd_id', $dcd->id)->count();
    }
}
