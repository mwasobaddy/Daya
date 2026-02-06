<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Referral;
use App\Services\VentureShareService;
use App\Services\QRCodeService;
use App\Mail\ReferralBonusNotification;
use App\Mail\DcdTokenAllocationNotification;
use App\Mail\WalletCreated;
use App\Mail\DcdWelcome;
use App\Mail\AdminDcdRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Mail;

class CreateTestDcd extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dcd:create-test {email : The email address for the test DCD}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a test DCD user and send registration emails';

    protected $ventureShareService;
    protected $qrCodeService;

    public function __construct(VentureShareService $ventureShareService, QRCodeService $qrCodeService)
    {
        parent::__construct();
        $this->ventureShareService = $ventureShareService;
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $this->info("Creating test DCD with email: {$email}");

        try {
            // Check if user already exists
            if (User::where('email', $email)->exists()) {
                $this->error("User with email {$email} already exists!");
                return;
            }

            // Get test data
            $ward = \App\Models\Ward::with('subcounty.county.country')->first();
            if (!$ward) {
                $this->error('No ward found in database. Please seed the database first.');
                return;
            }

            // Generate unique referral code
            do {
                $referralCode = Str::upper(Str::random(8));
            } while (User::where('referral_code', $referralCode)->exists());

            // Create the user
            $user = User::create([
                'name' => 'Test DCD User',
                'email' => $email,
                'password' => bcrypt('1234'),
                'role' => 'dcd',
                'national_id' => 'TEST' . rand(1000, 9999),
                'phone' => '+1234567890',
                'country_id' => $ward->subcounty->county->country->id,
                'county_id' => $ward->subcounty->county->id,
                'subcounty_id' => $ward->subcounty->id,
                'ward_id' => $ward->id,
                'wallet_pin' => bcrypt('1234'),
                'wallet_type' => 'business',
                'wallet_status' => 'active',
                'referral_code' => $referralCode,
                'profile' => [
                    'full_name' => 'Test DCD User',
                    'date_of_birth' => '1990-01-01',
                    'gender' => 'other',
                    'business_address' => 'Test Address',
                    'latitude' => null,
                    'longitude' => null,
                    'business_name' => 'Test Business',
                    'business_types' => ['cafe'],
                    'other_business_type' => null,
                    'daily_foot_traffic' => '11-50',
                    'operating_hours_start' => '09:00',
                    'operating_hours_end' => '17:00',
                    'operating_days' => ['monday', 'tuesday'],
                    'campaign_types' => ['events'],
                    'music_genres' => [],
                    'safe_for_kids' => true,
                    'terms_accepted' => true,
                    'terms_accepted_at' => now(),
                ],
            ]);

            $this->info("User created with ID: {$user->id}");

            // Generate QR code
            $qrFilename = $this->qrCodeService->generateDcdQr($user);
            $user->update(['qr_code' => $qrFilename]);

            // No referrer for test
            $referrer = null;

            // Send welcome email
            $this->info('Sending welcome email...');
            Mail::to($user->email)->send(new DcdWelcome($user, $referrer, $qrFilename));

            // Send wallet creation notification
            $this->info('Sending wallet creation email...');
            Mail::to($user->email)->send(new WalletCreated($user));

            // Allocate initial tokens
            $this->info('Allocating initial tokens...');
            $this->ventureShareService->allocateInitialDcdTokens($user);

            // Send token allocation notification
            $this->info('Sending token allocation email...');
            Mail::to($user->email)->send(new DcdTokenAllocationNotification($user, $this->ventureShareService));

            // Send admin notifications
            $adminUsers = User::where('role', 'admin')->get();
            if ($adminUsers->count() > 0) {
                $this->info("Sending notifications to {$adminUsers->count()} admins...");
                foreach ($adminUsers as $admin) {
                    Mail::to($admin->email)->send(new AdminDcdRegistration($user, $referrer));
                }
            }

            $this->info('Test DCD created successfully! Check your email for the three registration emails.');
            $this->info("User ID: {$user->id}, Email: {$email}, Referral Code: {$referralCode}");

        } catch (\Exception $e) {
            $this->error('Failed to create test DCD: ' . $e->getMessage());
            \Log::error('Test DCD creation failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        }
    }
}
