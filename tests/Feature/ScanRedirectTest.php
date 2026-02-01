<?php

use App\Models\Campaign;
use App\Models\User;
use App\Models\Scan;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('scan redirect records a scan and redirects to product', function () {
    // Setup location and users
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'TestDcd', 'account_type' => 'business', 'ward_id' => $ward->id]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Live Campaign',
        'budget' => 50,
        'campaign_credit' => 50,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
        'metadata' => ['business_name' => 'TestDcd', 'business_types' => ['business']],
    ]);

    $url = URL::temporarySignedRoute('scan.redirect', now()->addYear(), ['dcd' => $dcd->id, 'campaign' => $campaign->id]);

    // First, get the scan processing page
    $response = $this->get($url);
    $response->assertStatus(200);
    $response->assertViewIs('scan-processing');

    // Now simulate the API call that records the scan with fingerprint
    $apiResponse = $this->postJson('/api/scan/record-with-fingerprint', [
        'dcd_id' => $dcd->id,
        'campaign_id' => $campaign->id,
        'fingerprint' => 'test-fingerprint-123',
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
    expect($scan->device_fingerprint)->toBe('test-fingerprint-123');

    // Assert earning was created
    $earning = \App\Models\Earning::where('scan_id', $scan->id)->where('type', 'scan')->first();
    expect($earning)->not->toBeNull();
});
