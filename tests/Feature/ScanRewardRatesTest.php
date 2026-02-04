<?php

use App\Models\Campaign;
use App\Models\User;
use App\Models\Earning;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('music_promotion scan rewards KSh 1 by default', function () {
    // Setup location and users
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Music Campaign',
        'description' => 'Music',
        'budget' => 100,
        'cost_per_click' => 1.0,
        'campaign_credit' => 100,
        'county' => 'Example',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'None',
        'campaign_objective' => 'music_promotion',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
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
        'fingerprint' => 'test-fingerprint-music',
    ]);

    $apiResponse->assertStatus(200);
    $apiResponse->assertJson([
        'message' => 'Scan recorded successfully',
        'redirect_url' => 'https://example.com',
    ]);

    $scan = \App\Models\Scan::where('campaign_id', $campaign->id)->first();
    $earning = Earning::where('scan_id', $scan->id)->where('type', 'scan')->first();
    expect($earning)->not->toBeNull();
    expect((float)$earning->amount)->toBe(0.6); // DCD gets 60% of 1.0

    $scan = \App\Models\Scan::where('campaign_id', $campaign->id)->first();
    expect((float) $scan->earnings)->toBe(1.0);
});

test('app_downloads scan rewards KSh 5 by default', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);
    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'App Campaign',
        'description' => 'App download',
        'budget' => 100,
        'cost_per_click' => 5.0,
        'campaign_credit' => 100,
        'county' => 'Example',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'None',
        'campaign_objective' => 'app_downloads',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
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
        'fingerprint' => 'test-fingerprint-app',
    ]);

    $apiResponse->assertStatus(200);
    $apiResponse->assertJson([
        'message' => 'Scan recorded successfully',
        'redirect_url' => 'https://example.com',
    ]);

    $scan = \App\Models\Scan::where('campaign_id', $campaign->id)->first();
    $earning = Earning::where('scan_id', $scan->id)->where('type', 'scan')->first();
    expect($earning)->not->toBeNull();
    expect((float)$earning->amount)->toBe(3.0); // DCD gets 60% of 5.0

    $scan = \App\Models\Scan::where('campaign_id', $campaign->id)->first();
    expect((float) $scan->earnings)->toBe(5.0);
});

test('brand_awareness without explainer rewards KSh 1, with explainer rewards KSh 5', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);
    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);

    $campaignSimple = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Brand - Simple',
        'description' => 'BA Simple',
        'budget' => 100,
        'cost_per_click' => 1.0,
        'campaign_credit' => 100,
        'county' => 'Example',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'None',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
    ]);

    $campaignExplainer = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Brand - Explainer',
        'description' => 'BA Explainer',
        'budget' => 100,
        'cost_per_click' => 5.0,
        'campaign_credit' => 100,
        'county' => 'Example',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'None',
        'campaign_objective' => 'brand_awareness',
        'explainer_video_url' => 'https://youtube.com/vid',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
    ]);

    // Test simple brand awareness (KSh 1)
    $urlSimple = URL::temporarySignedRoute('scan.redirect', now()->addYear(), ['dcd' => $dcd->id, 'campaign' => $campaignSimple->id]);
    $responseSimple = $this->get($urlSimple);
    $responseSimple->assertStatus(200);
    $responseSimple->assertViewIs('scan-processing');

    $apiResponseSimple = $this->postJson('/api/scan/record-with-fingerprint', [
        'dcd_id' => $dcd->id,
        'campaign_id' => $campaignSimple->id,
        'fingerprint' => 'test-fingerprint-simple',
    ]);

    $apiResponseSimple->assertStatus(200);
    $scanSimple = \App\Models\Scan::where('campaign_id', $campaignSimple->id)->first();
    $earningSimple = Earning::where('scan_id', $scanSimple->id)->where('type', 'scan')->first();
    expect((float)$earningSimple->amount)->toBe(0.6); // DCD gets 60% of 1.0
    expect((float) $scanSimple->earnings)->toBe(1.0);

    // Test brand awareness with explainer (KSh 5)
    $urlExplainer = URL::temporarySignedRoute('scan.redirect', now()->addYear(), ['dcd' => $dcd->id, 'campaign' => $campaignExplainer->id]);
    $responseExplainer = $this->get($urlExplainer);
    $responseExplainer->assertStatus(200);
    $responseExplainer->assertViewIs('scan-processing');

    $apiResponseExplainer = $this->postJson('/api/scan/record-with-fingerprint', [
        'dcd_id' => $dcd->id,
        'campaign_id' => $campaignExplainer->id,
        'fingerprint' => 'test-fingerprint-explainer',
    ]);

    $apiResponseExplainer->assertStatus(200);
    $scanExplainer = \App\Models\Scan::where('campaign_id', $campaignExplainer->id)->first();
    $earningExplainer = Earning::where('scan_id', $scanExplainer->id)->where('type', 'scan')->first();
    expect((float)$earningExplainer->amount)->toBe(3.0); // DCD gets 60% of 5.0
    expect((float) $scanExplainer->earnings)->toBe(5.0);
});

test('music_promotion scan rewards N10 for Nigerian clients', function () {
    $kenya = \App\Models\Country::create(['code' => 'KE', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $nigeria = \App\Models\Country::create(['code' => 'NG', 'name' => 'Nigeria', 'county_label' => 'State', 'subcounty_label' => 'LGA']);
    $county = \App\Models\County::create(['country_id' => $nigeria->id, 'name' => 'Test State']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test LGA']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'country_id' => $nigeria->id, 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Nigerian Music Campaign',
        'description' => 'Music in Nigeria',
        'budget' => 100,
        'cost_per_click' => 10.0,
        'campaign_credit' => 100,
        'county' => 'Example',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'None',
        'campaign_objective' => 'music_promotion',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
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
        'fingerprint' => 'test-fingerprint-nigeria',
    ]);

    $apiResponse->assertStatus(200);
    $apiResponse->assertJson([
        'message' => 'Scan recorded successfully',
        'redirect_url' => 'https://example.com',
    ]);

    $scan = \App\Models\Scan::where('campaign_id', $campaign->id)->first();
    $earning = Earning::where('scan_id', $scan->id)->where('type', 'scan')->first();
    expect($earning)->not->toBeNull();
    expect((float)$earning->amount)->toBe(6.0); // DCD gets 60% of 10.0
    expect((float) $scan->earnings)->toBe(10.0);
});

test('apartment_listing scan rewards KSh 5 by default', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Apartment Listing Campaign',
        'description' => 'Real estate listings',
        'budget' => 100,
        'cost_per_click' => 5.0,
        'campaign_credit' => 100,
        'county' => 'Example',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'None',
        'campaign_objective' => 'apartment_listing',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
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
        'fingerprint' => 'test-fingerprint-apartment',
    ]);

    $apiResponse->assertStatus(200);
    $apiResponse->assertJson([
        'message' => 'Scan recorded successfully',
        'redirect_url' => 'https://example.com',
    ]);

    $scan = \App\Models\Scan::where('campaign_id', $campaign->id)->first();
    $earning = Earning::where('scan_id', $scan->id)->where('type', 'scan')->first();
    expect($earning)->not->toBeNull();
    expect((float)$earning->amount)->toBe(3.0); // DCD gets 60% of 5.0

    expect((float) $scan->earnings)->toBe(5.0);
});
