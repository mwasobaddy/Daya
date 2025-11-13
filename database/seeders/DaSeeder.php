<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Referral;
use App\Services\VentureShareService;
use App\Services\QRCodeService;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Find the admin user with referral code
        $admin = User::where('role', 'admin')
                    ->where('referral_code', '7a3H4P')
                    ->first();

        if (!$admin) {
            $this->command->error('Admin user with referral code 7a3H4P not found. Please run AdminSeeder first.');
            return;
        }

        $this->command->info('Creating Digital Ambassadors referred by admin: ' . $admin->name);

        // Sample DA data
        $digitalAmbassadors = [
            [
                'name' => 'Sarah Wanjiku',
                'email' => 'sarah.wanjiku@example.com',
                'phone' => '+254712345678',
                'national_id' => 'DA001234567',
                'ward_id' => 1,
                'profile_data' => [
                    'full_name' => 'Sarah Wanjiku Mwangi',
                    'date_of_birth' => '1995-03-15',
                    'gender' => 'female',
                    'physical_address' => 'Nairobi CBD, Kenya',
                    'interests' => ['music', 'movies', 'education'],
                    'device_type' => 'smartphone',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                ]
            ],
            [
                'name' => 'John Kimani',
                'email' => 'john.kimani@example.com',
                'phone' => '+254723456789',
                'national_id' => 'DA001234568',
                'ward_id' => 2,
                'profile_data' => [
                    'full_name' => 'John Kimani Mwangi',
                    'date_of_birth' => '1992-07-22',
                    'gender' => 'male',
                    'physical_address' => 'Westlands, Nairobi',
                    'interests' => ['games', 'mobile_apps', 'events'],
                    'device_type' => 'smartphone',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                ]
            ],
            [
                'name' => 'Grace Akinyi',
                'email' => 'grace.akinyi@example.com',
                'phone' => '+254734567890',
                'national_id' => 'DA001234569',
                'ward_id' => 3,
                'profile_data' => [
                    'full_name' => 'Grace Akinyi Ochieng',
                    'date_of_birth' => '1998-11-08',
                    'gender' => 'female',
                    'physical_address' => 'Kisumu Central',
                    'interests' => ['music', 'education', 'product_launch'],
                    'device_type' => 'tablet',
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                ]
            ]
        ];

        foreach ($digitalAmbassadors as $daData) {
            // Check if DA already exists
            if (User::where('email', $daData['email'])->exists()) {
                $this->command->warn('DA with email ' . $daData['email'] . ' already exists. Skipping.');
                continue;
            }

            // Create the DA user
            $da = User::create([
                'name' => $daData['name'],
                'email' => $daData['email'],
                'password' => Hash::make('1234'), // Default PIN as password
                'role' => 'da',
                'phone' => $daData['phone'],
                'national_id' => $daData['national_id'],
                'ward_id' => $daData['ward_id'],
                'wallet_pin' => Hash::make('1234'),
                'wallet_type' => 'personal',
                'wallet_status' => 'active',
                'referral_code' => Str::upper(Str::random(8)),
                'email_verified_at' => now(),
                'profile' => $daData['profile_data']
            ]);

            $this->command->info('Created DA: ' . $da->name . ' (' . $da->email . ')');

            // Generate QR code for the DA
            try {
                $qrCodeService = app(QRCodeService::class);
                $qrCodeFilename = $qrCodeService->generateDAReferralQRCode($da);
                $qrCodeUrl = $qrCodeService->getQRCodeUrl($qrCodeFilename);
                
                $da->update(['qr_code' => $qrCodeUrl]);
                $this->command->info('Generated QR code for: ' . $da->name);
            } catch (\Exception $e) {
                $this->command->warn('Could not generate QR code for ' . $da->name . ': ' . $e->getMessage());
            }

            // Create referral record
            $referral = Referral::create([
                'referrer_id' => $admin->id,
                'referred_id' => $da->id,
                'type' => 'admin_to_da',
            ]);

            $this->command->info('Created referral record: Admin -> ' . $da->name);

            // Allocate venture shares for the referral
            try {
                $ventureShareService = app(VentureShareService::class);
                $ventureShareService->allocateSharesForReferral($referral);
                $this->command->info('Allocated venture shares for referral: ' . $da->name);
            } catch (\Exception $e) {
                $this->command->warn('Could not allocate venture shares for ' . $da->name . ': ' . $e->getMessage());
            }
        }

        $this->command->info('DA Seeder completed successfully!');
    }
}