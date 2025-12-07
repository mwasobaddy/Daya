<?php

use App\Services\CampaignMatchingService;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('campaign matching prevents overlapping date ranges', function () {
    // Setup location and users
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'TestBusiness', 'account_type' => 'business', 'ward_id' => $ward->id]);

    $today = now()->format('Y-m-d');
    $tomorrow = now()->addDay()->format('Y-m-d');
    $dayAfter = now()->addDays(2)->format('Y-m-d');

    // Create existing campaign for DCD
    $existingCampaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Existing Campaign',
        'description' => 'Existing campaign',
        'budget' => 100,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => "$today to $tomorrow",
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
        'metadata' => [
            'business_name' => 'TestBusiness',
            'business_types' => ['business'],
            'start_date' => $today,
            'end_date' => $tomorrow,
        ],
    ]);

    // Try to create a new overlapping campaign
    $newCampaign = Campaign::create([
        'client_id' => $client->id,
        'title' => 'New Overlapping Campaign',
        'description' => 'New campaign that overlaps',
        'budget' => 150,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => "$tomorrow to $dayAfter",
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example2.com',
        'status' => 'submitted',
        'metadata' => [
            'business_name' => 'TestBusiness',
            'business_types' => ['business'],
            'start_date' => $tomorrow,
            'end_date' => $dayAfter,
        ],
    ]);

    $matchingService = app(CampaignMatchingService::class);
    $assignedDcd = $matchingService->assignDcd($newCampaign);

    // Should not assign the same DCD due to overlapping dates
    expect($assignedDcd)->toBeNull();
});

test('campaign matching allows non-overlapping date ranges', function () {
    // Setup location and users
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'TestBusiness', 'account_type' => 'business', 'ward_id' => $ward->id]);

    $today = now()->format('Y-m-d');
    $tomorrow = now()->addDay()->format('Y-m-d');
    $nextWeek = now()->addWeek()->format('Y-m-d');
    $weekAfter = now()->addWeek()->addDay()->format('Y-m-d');

    // Create existing campaign for DCD
    $existingCampaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Existing Campaign',
        'description' => 'Existing campaign',
        'budget' => 100,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => "$today to $tomorrow",
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
        'metadata' => [
            'business_name' => 'TestBusiness',
            'business_types' => ['business'],
            'start_date' => $today,
            'end_date' => $tomorrow,
        ],
    ]);

    // Try to create a new non-overlapping campaign
    $newCampaign = Campaign::create([
        'client_id' => $client->id,
        'title' => 'New Non-overlapping Campaign',
        'description' => 'New campaign that does not overlap',
        'budget' => 150,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => "$nextWeek to $weekAfter",
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example2.com',
        'status' => 'submitted',
        'metadata' => [
            'business_name' => 'TestBusiness',
            'business_types' => ['business'],
            'start_date' => $nextWeek,
            'end_date' => $weekAfter,
        ],
    ]);

    $matchingService = app(CampaignMatchingService::class);
    $assignedDcd = $matchingService->assignDcd($newCampaign);

    // Should assign the same DCD since dates don't overlap
    expect($assignedDcd->id)->toBe($dcd->id);
    expect($newCampaign->fresh()->dcd_id)->toBe($dcd->id);
});