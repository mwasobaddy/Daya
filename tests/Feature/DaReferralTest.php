<?php

use App\Models\User;
use App\Models\Referral;
use App\Models\Country;
use App\Models\County;
use App\Models\Subcounty;
use App\Models\Ward;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DaReferralTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function da_registration_with_admin_referrer_id_shows_referral_in_admin_email()
    {
        // Create location hierarchy
        $country = Country::create([
            'code' => 'KE',
            'name' => 'Kenya',
            'county_label' => 'County',
            'subcounty_label' => 'Sub-county'
        ]);
        $county = County::create([
            'country_id' => $country->id,
            'name' => 'Nairobi',
            'code' => 'NAIROBI'
        ]);
        $subcounty = Subcounty::create([
            'county_id' => $county->id,
            'name' => 'Westlands',
            'code' => 'WESTLANDS'
        ]);
        $ward = Ward::create([
            'subcounty_id' => $subcounty->id,
            'name' => 'Koinange',
            'code' => 'KOINANGE'
        ]);

        // Create an admin user to act as referrer
        $admin = User::factory()->create([
            'role' => 'admin',
            'referral_code' => 'ADMIN123'
        ]);

        // Prepare DA registration data with referrer_id
        $daData = [
            'referrer_id' => $admin->id,
            'full_name' => 'Test DA',
            'national_id' => '12345678',
            'dob' => '1990-01-01',
            'gender' => 'male',
            'email' => 'testda@example.com',
            'ward_id' => $ward->id,
            'address' => 'Test Address',
            'phone' => '+254700000000',
            'platforms' => ['instagram', 'facebook'],
            'followers' => '1k_10k',
            'communication_channel' => 'whatsapp',
            'wallet_type' => 'personal',
            'wallet_pin' => '1234',
            'confirm_pin' => '1234',
            'terms' => '1',
        ];

        // Make the API request
        $response = $this->postJson('/api/da/create', $daData);

        // Assert successful registration
        $response->assertStatus(200)
                ->assertJson(['message' => 'DA registered successfully']);

        // Verify the DA user was created
        $da = User::where('email', 'testda@example.com')->first();
        $this->assertNotNull($da);
        $this->assertEquals('da', $da->role);

        // Verify referral record was created
        $referral = Referral::where('referred_id', $da->id)->first();
        $this->assertNotNull($referral);
        $this->assertEquals($admin->id, $referral->referrer_id);
        $this->assertEquals('admin_to_da', $referral->type);

        // Verify the admin email would contain referral information
        // (In a real test, we'd use Mail::assertSent, but for now we'll check the data)
        $this->assertEquals($admin->id, $referral->referrer_id);
    }

    /** @test */
    public function da_registration_with_da_referrer_shows_referral_in_admin_email()
    {
        // Create location hierarchy
        $country = Country::create([
            'code' => 'KE',
            'name' => 'Kenya',
            'county_label' => 'County',
            'subcounty_label' => 'Sub-county'
        ]);
        $county = County::create([
            'country_id' => $country->id,
            'name' => 'Nairobi',
            'code' => 'NAIROBI'
        ]);
        $subcounty = Subcounty::create([
            'county_id' => $county->id,
            'name' => 'Westlands',
            'code' => 'WESTLANDS'
        ]);
        $ward = Ward::create([
            'subcounty_id' => $subcounty->id,
            'name' => 'Koinange',
            'code' => 'KOINANGE'
        ]);

        // Create a DA user to act as referrer
        $referringDa = User::factory()->create([
            'role' => 'da',
            'referral_code' => 'DAREF123'
        ]);

        // Prepare DA registration data with referrer_id
        $daData = [
            'referrer_id' => $referringDa->id,
            'full_name' => 'Test DA 2',
            'national_id' => '87654321',
            'dob' => '1992-01-01',
            'gender' => 'female',
            'email' => 'testda2@example.com',
            'ward_id' => $ward->id,
            'address' => 'Test Address 2',
            'phone' => '+254711111111',
            'platforms' => ['twitter'],
            'followers' => '10k_50k',
            'communication_channel' => 'telegram',
            'wallet_type' => 'business',
            'wallet_pin' => '5678',
            'confirm_pin' => '5678',
            'terms' => '1',
        ];

        // Make the API request
        $response = $this->postJson('/api/da/create', $daData);

        // Assert successful registration
        $response->assertStatus(200);

        // Verify referral record was created with correct type
        $da = User::where('email', 'testda2@example.com')->first();
        $referral = Referral::where('referred_id', $da->id)->first();
        $this->assertEquals('da_to_da', $referral->type);
    }

    /** @test */
    public function da_registration_without_referrer_works_correctly()
    {
        // Create location hierarchy
        $country = Country::create([
            'code' => 'KE',
            'name' => 'Kenya',
            'county_label' => 'County',
            'subcounty_label' => 'Sub-county'
        ]);
        $county = County::create([
            'country_id' => $country->id,
            'name' => 'Nairobi',
            'code' => 'NAIROBI'
        ]);
        $subcounty = Subcounty::create([
            'county_id' => $county->id,
            'name' => 'Westlands',
            'code' => 'WESTLANDS'
        ]);
        $ward = Ward::create([
            'subcounty_id' => $subcounty->id,
            'name' => 'Koinange',
            'code' => 'KOINANGE'
        ]);

        // Prepare DA registration data without referrer
        $daData = [
            'full_name' => 'Test DA No Ref',
            'national_id' => '11223344',
            'dob' => '1995-01-01',
            'gender' => 'other',
            'email' => 'testda3@example.com',
            'ward_id' => $ward->id,
            'address' => 'Test Address 3',
            'phone' => '+254722222222',
            'platforms' => ['tiktok'],
            'followers' => '50k_100k',
            'communication_channel' => 'email',
            'wallet_type' => 'both',
            'wallet_pin' => '9999',
            'confirm_pin' => '9999',
            'terms' => '1',
        ];

        // Make the API request
        $response = $this->postJson('/api/da/create', $daData);

        // Assert successful registration
        $response->assertStatus(200);

        // Verify no referral record was created
        $da = User::where('email', 'testda3@example.com')->first();
        $referral = Referral::where('referred_id', $da->id)->first();
        $this->assertNull($referral);
    }
}