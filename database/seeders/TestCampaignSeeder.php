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
                'phone' => '+254700000001',
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
                'phone' => '+254700000002',
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
                'phone' => '+254700000003',
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

        // 4. Create approved campaigns for the client (up to 3 for DCD)
        $campaignTitles = [
            'Test Campaign for Earnings 1',
            'Test Campaign for Earnings 2', 
            'Test Campaign for Earnings 3'
        ];
        
        foreach ($campaignTitles as $index => $title) {
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
                    'objectives' => 'Test the new 60-30-10 earnings allocation system',
                    // generate random links for testing
                    'digital_product_link' => 'https://example.com/product/' . Str::slug($title),
                    'explainer_video_url' => 'https://example.com/explainer-video',
                    'metadata' => [
                        'start_date' => '2026-01-01',
                        'end_date' => '2026-12-' . (31 - $index * 5), // Vary end dates: 31, 26, 21
                        'target_regions' => ['Nairobi CBD', 'Westlands'],
                        'expected_conversion_rate' => '15%',
                    ],
                    'completed_at' => null,
                ]);
                $this->command->info('Created live campaign: ' . $campaign->title);
            }
        }

        // 5. Generate QR code for the DCD
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
        $this->command->info('Campaign Details:');
        $this->command->info('  Title: Test Campaign for Earnings');
        $this->command->info('  Budget: KSH 1,000');
        $this->command->info('  Cost per scan: KSH 5');
        $this->command->info('  Status: live (ready for scanning)');
        $this->command->info('  Date Range: 2026-01-01 to 2026-12-31');
        $this->command->info('  Expected earnings per scan:');
        $this->command->info('    - DCD: KSH 3.00 (60%)');
        $this->command->info('    - Company (Daya): KSH 1.50 (30%)');
        $this->command->info('    - DA (Referrer): KSH 0.50 (10%)');
        $this->command->info('');
        $this->command->info('To test: Scan the DCD\'s QR code and verify earnings are created with the 60-30-10 split.');
    }
}