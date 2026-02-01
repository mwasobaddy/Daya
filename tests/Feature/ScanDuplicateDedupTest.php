<?php

use App\Models\Campaign;
use App\Models\User;
use App\Models\Earning;
use App\Models\Scan;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// Assert that two scans with same device fingerprint within short time only produce one earning

test('duplicate scan with same fingerprint is single earning', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Duplicate Test',
        'description' => 'Duplicate test',
        'budget' => 1000,
        'campaign_credit' => 1000, // Initialize campaign credit
        'county' => 'Example',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'None',
        'campaign_objective' => 'music_promotion',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
        'metadata' => [
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ],
    ]);

    // Simulate first scan with fingerprint
    $svc = app(App\Services\QRCodeService::class);
    $result1 = $svc->recordCampaignScan($dcd->id, $campaign->id, ['fingerprint' => 'fp-123']);
    $scan1 = $result1['scan'];

    // Simulate second scan using same fingerprint
    $scan2 = $svc->recordCampaignScan($dcd->id, $campaign->id, ['fingerprint' => 'fp-123']);

    $earnings = Earning::where('user_id', $dcd->id)->where('type', 'scan')->get();
    expect($earnings->count())->toBe(1);
    expect($scan1->id)->not->toBeNull();
});


// Assert that different fingerprints create separate earnings

test('distinct fingerprint creates separate earning', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Duplicate Test 2',
        'description' => 'Duplicate test 2',
        'budget' => 1000,
        'campaign_credit' => 1000, // Initialize campaign credit
        'county' => 'Example',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'None',
        'campaign_objective' => 'music_promotion',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
        'metadata' => [
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(30)->format('Y-m-d'),
        ],
    ]);

    $svc = app(App\Services\QRCodeService::class);
    $svc->recordCampaignScan($dcd->id, $campaign->id, ['fingerprint' => 'fp-123']);
    $svc->recordCampaignScan($dcd->id, $campaign->id, ['fingerprint' => 'fp-456']);

    $earnings = Earning::where('user_id', $dcd->id)->where('type', 'scan')->get();
    expect($earnings->count())->toBe(2);
});
