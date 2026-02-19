<?php

use App\Models\Campaign;
use App\Models\User;
use App\Services\CampaignMatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('assigns a dcd matching business name', function () {
    // Create a Country/County/Subcounty/Ward since users require ward_id
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

    $dcd = User::factory()->create([
        'role' => 'dcd',
        'business_name' => 'ShopsRUs',
        'account_type' => 'business',
        'ward_id' => $ward->id,
    ]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'title' => 'Test Campaign',
        'description' => 'Test description',
        'budget' => 100,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'submitted',
        'metadata' => ['business_name' => 'ShopsRUs', 'business_types' => ['business']],
    ]);

    $svc = app(CampaignMatchingService::class);

    $assigned = $svc->assignDcd($campaign);

    expect($assigned)->not->toBeNull();
    expect($assigned->id)->toBe($dcd->id);
    expect($campaign->fresh()->dcd_id)->toBe($dcd->id);
});

test('assigns a dcd matching campaign objective to campaign types', function () {
    // Create a Country/County/Subcounty/Ward since users require ward_id
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

    // Create DCD with mobile_apps in campaign_types (should match app_downloads campaigns)
    $dcd = User::factory()->create([
        'role' => 'dcd',
        'business_name' => 'AppDistributor',
        'account_type' => 'business',
        'ward_id' => $ward->id,
        'profile' => [
            'campaign_types' => ['mobile_apps', 'games']
        ]
    ]);

    // Create a campaign with app_downloads objective
    $campaign = Campaign::create([
        'client_id' => $client->id,
        'title' => 'App Download Campaign',
        'description' => 'Promote our new mobile app',
        'budget' => 200,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'Increase app downloads',
        'campaign_objective' => 'app_downloads',
        'digital_product_link' => 'https://play.google.com/store/apps/details?id=com.example.app',
        'status' => 'submitted',
        'metadata' => ['business_types' => ['startup']],
    ]);

    $svc = app(CampaignMatchingService::class);

    $assigned = $svc->assignDcd($campaign);

    expect($assigned)->not->toBeNull();
    expect($assigned->id)->toBe($dcd->id);
    expect($campaign->fresh()->dcd_id)->toBe($dcd->id);
});

test('assigns a dcd matching music promotion to music campaign types', function () {
    // Create a Country/County/Subcounty/Ward since users require ward_id
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

    // Create DCD with music in campaign_types
    $dcd = User::factory()->create([
        'role' => 'dcd',
        'business_name' => 'MusicHub',
        'account_type' => 'business', 
        'ward_id' => $ward->id,
        'profile' => [
            'campaign_types' => ['music', 'events'],
            'music_genres' => ['Hip Hop', 'Afrobeats']
        ]
    ]);

    // Create a campaign with music_promotion objective
    $campaign = Campaign::create([
        'client_id' => $client->id,
        'title' => 'Music Promotion Campaign',
        'description' => 'Promote new music releases',
        'budget' => 150,
        'county' => 'Example County',
        'target_audience' => 'Music Lovers',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'Increase music streams',
        'campaign_objective' => 'music_promotion',
        'digital_product_link' => 'https://music.example.com/artist/song',
        'status' => 'submitted',
        'metadata' => ['business_types' => ['artist']],
    ]);

    $svc = app(CampaignMatchingService::class);

    $assigned = $svc->assignDcd($campaign);

    expect($assigned)->not->toBeNull();
    expect($assigned->id)->toBe($dcd->id);
    expect($campaign->fresh()->dcd_id)->toBe($dcd->id);
});

test('does not select dcd with an active campaign', function () {
    // Create Country/County/Subcounty/Ward and client (same as first test)
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

    $dcd = User::factory()->create([
        'role' => 'dcd',
        'business_name' => 'ShopsRUs',
        'account_type' => 'business',
        'ward_id' => $ward->id,
    ]);

    // Create 3 active campaigns for this dcd (reaching the limit)
    for ($i = 1; $i <= 3; $i++) {
        Campaign::create([
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Existing Active Campaign ' . $i,
            'budget' => 200,
            'county' => 'Example County',
            'target_audience' => 'General Audience',
            'duration' => '2025-11-17 to 2025-11-20',
            'objectives' => 'Test objectives',
            'campaign_objective' => 'brand_awareness',
            'digital_product_link' => 'https://example.com',
            'status' => 'live',
            'metadata' => ['business_name' => 'ShopsRUs', 'business_types' => ['business'], 'start_date' => now()->format('Y-m-d'), 'end_date' => now()->addDays(30)->format('Y-m-d')],
        ]);
    }

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'title' => 'New Campaign',

        'budget' => 100,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'submitted',
        'metadata' => ['business_name' => 'ShopsRUs', 'business_types' => ['business']],
    ]);

    $svc = app(CampaignMatchingService::class);

    $assigned = $svc->assignDcd($campaign);

    expect($assigned)->toBeNull();
    expect($campaign->fresh()->dcd_id)->toBeNull();
});

test('assigns a dcd matching music genres and preferred country', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

    // Create DCD with profile music genres
    $dcd = User::factory()->create([
        'role' => 'dcd',
        'business_name' => 'MusicHouse',
        'account_type' => 'business',
        'ward_id' => $ward->id,
        'country_id' => $country->id,
        'profile' => ['music_genres' => ['Afrobeats', 'Electronic']],
    ]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'title' => 'Genre Campaign',
        'description' => 'Test description',
        'budget' => 100,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'Test objectives',
        'campaign_objective' => 'music_promotion',
        'digital_product_link' => 'https://example.com',
        'status' => 'submitted',
        'metadata' => [
            'music_genres' => ['Afrobeats'],
            'target_country' => 'KEN',
        ],
    ]);

    $svc = app(CampaignMatchingService::class);

    $assigned = $svc->assignDcd($campaign);

    expect($assigned)->not->toBeNull();
    expect($assigned->id)->toBe($dcd->id);
    expect($campaign->fresh()->dcd_id)->toBe($dcd->id);
});

test('assigns a dcd matching business types in profile', function () {
    // Create a Country/County/Subcounty/Ward since users require ward_id
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

    $dcd = User::factory()->create([
        'role' => 'dcd',
        'business_name' => 'UniqueBusiness',
        'account_type' => 'business',
        'ward_id' => $ward->id,
        'profile' => [
            'business_types' => ['retail', 'food']
        ]
    ]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'title' => 'Business Campaign',
        'description' => 'Promote retail business',
        'budget' => 150,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'Increase sales',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'submitted',
        'metadata' => [
            'business_name' => 'DifferentBusiness', // No match
            'business_types' => ['retail'], // Should match
            'start_date' => '2025-11-17',
            'end_date' => '2025-11-20',
        ],
    ]);

    $svc = app(CampaignMatchingService::class);

    $assigned = $svc->assignDcd($campaign);

    expect($assigned)->not->toBeNull();
    expect($assigned->id)->toBe($dcd->id);
    expect($campaign->fresh()->dcd_id)->toBe($dcd->id);
});

test('sets campaign status to live when dcd is assigned', function () {
    // Create a Country/County/Subcounty/Ward since users require ward_id
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

    $dcd = User::factory()->create([
        'role' => 'dcd',
        'business_name' => 'ShopsRUs',
        'account_type' => 'business',
        'ward_id' => $ward->id,
    ]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'title' => 'Test Campaign',
        'description' => 'Test description',
        'budget' => 100,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'active', // Start with active status
        'metadata' => ['business_name' => 'ShopsRUs', 'business_types' => ['business']],
    ]);

    $svc = app(CampaignMatchingService::class);

    $assigned = $svc->assignDcd($campaign);

    expect($assigned)->not->toBeNull();
    expect($assigned->id)->toBe($dcd->id);
    expect($campaign->fresh()->dcd_id)->toBe($dcd->id);
    expect($campaign->fresh()->status)->toBe('live'); // Verify status is set to live
});
