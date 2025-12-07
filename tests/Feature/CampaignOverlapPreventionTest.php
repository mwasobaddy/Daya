<?php

use App\Services\CampaignMatchingService;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

test('campaign matching prevents overlapping date ranges', function () {
    // Setup location
    $country = \App\Models\Country::create(['code' => 'KE', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    // Create two DCDs
    $dcd1 = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id, 'business_name' => 'Test Business']);
    $dcd2 = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id, 'business_name' => 'Other Business']);

    // Assign first campaign to DCD1
    $campaign1 = Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'dcd_id' => $dcd1->id,
        'title' => 'First Campaign',
        'description' => 'Running campaign',
        'budget' => 100,
        'county' => 'Test County',
        'status' => 'approved',
        'target_audience' => 'General audience',
        'duration' => 'Campaign duration',
        'objectives' => 'Campaign objectives',
        'digital_product_link' => 'https://example.com',
        'campaign_objective' => 'music_promotion',
        'metadata' => [
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
            'business_name' => 'Test Business',
        ]
    ]);

    // Create second campaign with overlapping dates
    $campaign2 = Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'title' => 'Second Campaign',
        'description' => 'Overlapping campaign',
        'budget' => 200,
        'county' => 'Test County',
        'status' => 'submitted',
        'target_audience' => 'General audience',
        'duration' => 'Campaign duration',
        'objectives' => 'Campaign objectives',
        'digital_product_link' => 'https://example.com',
        'campaign_objective' => 'music_promotion',
        'metadata' => [
            'start_date' => Carbon::today()->addDays(3)->format('Y-m-d'), // Overlaps with first campaign
            'end_date' => Carbon::today()->addWeeks(2)->format('Y-m-d'),
            'business_name' => 'Test Business',
        ]
    ]);

    $matchingService = new CampaignMatchingService();
    $assignedDcd = $matchingService->assignDcd($campaign2);

    // Should not assign to DCD1 due to overlapping dates, should assign to DCD2 or null
    expect($assignedDcd)->not->toBe($dcd1);
    if ($assignedDcd) {
        expect($assignedDcd->id)->toBe($dcd2->id);
    }
});

test('campaign matching allows non-overlapping date ranges', function () {
    // Setup location
    $country = \App\Models\Country::create(['code' => 'KE', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id, 'business_name' => 'Test Business']);

    // Create first campaign
    Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'dcd_id' => $dcd->id,
        'title' => 'First Campaign',
        'description' => 'Finished campaign',
        'budget' => 100,
        'county' => 'Test County',
        'status' => 'completed',
        'target_audience' => 'General audience',
        'duration' => 'Campaign duration',
        'objectives' => 'Campaign objectives',
        'digital_product_link' => 'https://example.com',
        'campaign_objective' => 'music_promotion',
        'metadata' => [
            'start_date' => Carbon::today()->subWeeks(2)->format('Y-m-d'),
            'end_date' => Carbon::today()->subWeek()->format('Y-m-d'),
            'business_name' => 'Test Business',
        ]
    ]);

    // Create second campaign with non-overlapping dates (future)
    $campaign2 = Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'title' => 'Second Campaign',
        'description' => 'Future campaign',
        'budget' => 200,
        'county' => 'Test County',
        'status' => 'submitted',
        'target_audience' => 'General audience',
        'duration' => 'Campaign duration',
        'objectives' => 'Campaign objectives',
        'digital_product_link' => 'https://example.com',
        'campaign_objective' => 'music_promotion',
        'metadata' => [
            'start_date' => Carbon::today()->addWeek()->format('Y-m-d'),
            'end_date' => Carbon::today()->addWeeks(2)->format('Y-m-d'),
            'business_name' => 'Test Business',
        ]
    ]);

    $matchingService = new CampaignMatchingService();
    $assignedDcd = $matchingService->assignDcd($campaign2);

    // Should assign to the DCD since dates don't overlap
    expect($assignedDcd)->not->toBeNull();
    expect($assignedDcd->id)->toBe($dcd->id);
    expect($campaign2->fresh()->dcd_id)->toBe($dcd->id);
});