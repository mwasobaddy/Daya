<?php

use App\Services\QRCodeService;
use App\Models\User;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('smart campaign selection finds active campaign for dcd', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);
    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'TestDcd', 'account_type' => 'business', 'ward_id' => $ward->id]);
    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

    $today = now()->format('Y-m-d');
    $tomorrow = now()->addDay()->format('Y-m-d');

    // Create an active campaign for today
    $activeCampaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Active Campaign',

        'budget' => 100,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => "$today to $tomorrow",
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
        'metadata' => [
            'business_name' => 'TestDcd', 
            'business_types' => ['business'],
            'start_date' => $today,
            'end_date' => $tomorrow,
        ],
    ]);

    $svc = app(QRCodeService::class);
    $result = $svc->recordDcdScan($dcd->id, null);

    expect($result['campaign']->id)->toBe($activeCampaign->id);
    expect($result['scan']->dcd_id)->toBe($dcd->id);
    expect($result['scan']->campaign_id)->toBe($activeCampaign->id);
});

test('smart campaign selection throws exception when no active campaigns', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);
    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'TestDcd', 'account_type' => 'business', 'ward_id' => $ward->id]);

    $svc = app(QRCodeService::class);
    
    expect(fn() => $svc->recordDcdScan($dcd->id, null))
        ->toThrow(\InvalidArgumentException::class, 'No active campaigns found for this DCD');
});

test('smart campaign selection prioritizes oldest campaigns first', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);
    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'TestDcd', 'account_type' => 'business', 'ward_id' => $ward->id]);
    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

    $today = now()->format('Y-m-d');
    $tomorrow = now()->addDay()->format('Y-m-d');

    // Create newer campaign first
    $newerCampaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Newer Campaign',

        'budget' => 200,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => "$today to $tomorrow",
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://newer.example.com',
        'status' => 'approved',
        'metadata' => [
            'business_name' => 'TestDcd', 
            'business_types' => ['business'],
            'start_date' => $today,
            'end_date' => $tomorrow,
        ],
    ]);

    // Create older campaign with earlier timestamp
    $olderCampaign = new Campaign([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Older Campaign',

        'budget' => 100,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => "$today to $tomorrow",
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://older.example.com',
        'status' => 'approved',
        'metadata' => [
            'business_name' => 'TestDcd', 
            'business_types' => ['business'],
            'start_date' => $today,
            'end_date' => $tomorrow,
        ],
    ]);
    $olderCampaign->created_at = now()->subHour();
    $olderCampaign->save();

    $svc = app(QRCodeService::class);
    $result = $svc->recordDcdScan($dcd->id, null);

    // Should prioritize the older campaign
    expect($result['campaign']->id)->toBe($olderCampaign->id);
});