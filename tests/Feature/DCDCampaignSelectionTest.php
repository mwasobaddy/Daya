<?php

use App\Services\DCDCampaignSelectionService;
use App\Models\User;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

test('returns oldest active campaign for DCD', function () {
    // Setup location
    $country = \App\Models\Country::create(['code' => 'KE', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);
    
    // Create two campaigns, second one created later (should be deprioritized)
    $olderCampaign = Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'dcd_id' => $dcd->id,
        'title' => 'Older Campaign',
        'description' => 'First campaign',
        'budget' => 100,
        'county' => 'Test County',
        'status' => 'approved',
        'target_audience' => 'General audience',
        'duration' => 'Campaign duration',
        'objectives' => 'Campaign objectives',
        'digital_product_link' => 'https://example.com',
        'campaign_objective' => 'music_promotion',
        'metadata' => [
            'start_date' => Carbon::today()->subDay()->format('Y-m-d'),
            'end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
        ]
    ]);

    // Sleep to ensure different created_at timestamps
    sleep(1);

    $newerCampaign = Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'dcd_id' => $dcd->id,
        'title' => 'Newer Campaign',
        'description' => 'Second campaign',
        'budget' => 200,
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
        ]
    ]);

    $service = new DCDCampaignSelectionService();
    $selectedCampaign = $service->getActiveCampaignForDCD($dcd);

    expect($selectedCampaign)->not->toBeNull();
    expect($selectedCampaign->id)->toBe($olderCampaign->id);
    expect($selectedCampaign->title)->toBe('Older Campaign');
});

test('returns null when no active campaigns', function () {
    $dcd = User::factory()->create(['role' => 'dcd']);
    
    // Create expired campaign
    Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'dcd_id' => $dcd->id,
        'title' => 'Expired Campaign',
        'description' => 'Old campaign',
        'budget' => 100,
        'county' => 'Test County',
        'status' => 'approved',
        'target_audience' => 'General audience',
        'duration' => 'Campaign duration',
        'objectives' => 'Campaign objectives',
        'digital_product_link' => 'https://example.com',
        'campaign_objective' => 'music_promotion',
        'metadata' => [
            'start_date' => Carbon::today()->subWeeks(2)->format('Y-m-d'),
            'end_date' => Carbon::today()->subWeek()->format('Y-m-d'),
        ]
    ]);

    $service = new DCDCampaignSelectionService();
    $selectedCampaign = $service->getActiveCampaignForDCD($dcd);

    expect($selectedCampaign)->toBeNull();
});

test('ignores future campaigns', function () {
    $dcd = User::factory()->create(['role' => 'dcd']);
    
    // Create future campaign
    Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'dcd_id' => $dcd->id,
        'title' => 'Future Campaign',
        'description' => 'Starts tomorrow',
        'budget' => 100,
        'county' => 'Test County',
        'status' => 'approved',
        'target_audience' => 'General audience',
        'duration' => 'Campaign duration',
        'objectives' => 'Campaign objectives',
        'digital_product_link' => 'https://example.com',
        'campaign_objective' => 'music_promotion',
        'metadata' => [
            'start_date' => Carbon::tomorrow()->format('Y-m-d'),
            'end_date' => Carbon::tomorrow()->addWeek()->format('Y-m-d'),
        ]
    ]);

    $service = new DCDCampaignSelectionService();
    $selectedCampaign = $service->getActiveCampaignForDCD($dcd);

    expect($selectedCampaign)->toBeNull();
});

test('handles malformed campaign dates gracefully', function () {
    $dcd = User::factory()->create(['role' => 'dcd']);
    
    // Create campaign with invalid dates
    Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'dcd_id' => $dcd->id,
        'title' => 'Invalid Date Campaign',
        'description' => 'Bad dates',
        'budget' => 100,
        'county' => 'Test County',
        'status' => 'approved',
        'target_audience' => 'General audience',
        'duration' => 'Campaign duration',
        'objectives' => 'Campaign objectives',
        'digital_product_link' => 'https://example.com',
        'campaign_objective' => 'music_promotion',
        'metadata' => [
            'start_date' => 'invalid-date',
            'end_date' => 'also-invalid',
        ]
    ]);

    $service = new DCDCampaignSelectionService();
    $selectedCampaign = $service->getActiveCampaignForDCD($dcd);

    expect($selectedCampaign)->toBeNull();
});

test('returns appropriate message when no active campaigns', function () {
    $service = new DCDCampaignSelectionService();
    $message = $service->getNoActiveCampaignMessage();
    
    expect($message)->toBe("No active campaigns right now, try again later");
});