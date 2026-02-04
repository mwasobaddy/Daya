<?php

use App\Models\Campaign;
use App\Models\User;
use App\Models\Scan;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('dcd scan redirect finds active campaign and redirects to product', function () {
    // Setup location and users
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'TestDcd', 'account_type' => 'business', 'ward_id' => $ward->id]);

    $today = now()->format('Y-m-d');
    $tomorrow = now()->addDay()->format('Y-m-d');

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Live Campaign',
        'budget' => 50,
        'campaign_credit' => 50,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => "$today to $tomorrow",
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'live',
        'metadata' => [
            'business_name' => 'TestDcd', 
            'business_types' => ['business'],
            'start_date' => $today,
            'end_date' => $tomorrow,
        ],
    ]);

    // Test the new DCD scan route
    $url = URL::temporarySignedRoute('scan.dcd', now()->addYear(), ['dcd' => $dcd->id]);

    // First, get the scan processing page
    $response = $this->get($url);
    $response->assertStatus(200);
    $response->assertViewIs('scan-processing');

    // Now simulate the API call that records the scan with fingerprint
    $apiResponse = $this->postJson('/api/scan/record-with-fingerprint', [
        'dcd_id' => $dcd->id,
        'campaign_id' => null, // Smart selection
        'fingerprint' => 'test-fingerprint-456',
    ]);

    $apiResponse->assertStatus(200);
    $apiResponse->assertJson([
        'message' => 'Scan recorded successfully',
        'redirect_url' => 'https://example.com',
    ]);

    // Assert a scan record exists
    $scan = Scan::where('campaign_id', $campaign->id)->first();
    expect($scan)->not->toBeNull();
    expect($scan->dcd_id)->toBe($dcd->id);
    expect($scan->device_fingerprint)->toBe('test-fingerprint-456');
});

test('dcd scan redirect shows no active campaigns message when no campaigns', function () {
    // Setup location and users
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'TestDcd', 'account_type' => 'business', 'ward_id' => $ward->id]);

    // Test the new DCD scan route with no campaigns
    $url = URL::temporarySignedRoute('scan.dcd', now()->addYear(), ['dcd' => $dcd->id]);

    // First, get the scan processing page
    $response = $this->get($url);
    $response->assertStatus(200);
    $response->assertViewIs('scan-processing');

    // Now simulate the API call - should fail with no active campaigns
    $apiResponse = $this->postJson('/api/scan/record-with-fingerprint', [
        'dcd_id' => $dcd->id,
        'campaign_id' => null,
        'fingerprint' => 'test-fingerprint-no-campaigns',
    ]);

    $apiResponse->assertStatus(400);
    $apiResponse->assertJson([
        'message' => 'No active campaigns found for this DCD',
        'error_type' => 'no_campaigns',
    ]);
});

test('dcd scan redirect shows no active campaigns when campaign is expired', function () {
    // Setup location and users
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'TestDcd', 'account_type' => 'business', 'ward_id' => $ward->id]);

    $yesterday = now()->subDay()->format('Y-m-d');
    $today = now()->format('Y-m-d');

    // Create an expired campaign
    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Expired Campaign',

        'budget' => 50,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => "$yesterday to $yesterday",
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
        'metadata' => [
            'business_name' => 'TestDcd', 
            'business_types' => ['business'],
            'start_date' => $yesterday,
            'end_date' => $yesterday,
        ],
    ]);

    // Test the new DCD scan route
    $url = URL::temporarySignedRoute('scan.dcd', now()->addYear(), ['dcd' => $dcd->id]);

    // First, get the scan processing page
    $response = $this->get($url);
    $response->assertStatus(200);
    $response->assertViewIs('scan-processing');

    // Now simulate the API call - should fail with no active campaigns (expired)
    $apiResponse = $this->postJson('/api/scan/record-with-fingerprint', [
        'dcd_id' => $dcd->id,
        'campaign_id' => null,
        'fingerprint' => 'test-fingerprint-expired',
    ]);

    $apiResponse->assertStatus(400);
    $apiResponse->assertJson([
        'message' => 'No active campaigns found for this DCD',
        'error_type' => 'no_campaigns',
    ]);
});