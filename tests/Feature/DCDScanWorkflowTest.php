<?php

use App\Http\Controllers\DCDScanController;
use App\Models\User;
use App\Models\Campaign;
use App\Models\Scan;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

test('DCD scan redirects to active campaign', function () {
    // Setup location
    $country = \App\Models\Country::create(['code' => 'KE', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);
    
    $campaign = Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'dcd_id' => $dcd->id,
        'title' => 'Active Campaign',
        'description' => 'Currently active',
        'budget' => 100,
        'county' => 'Test County',
        'digital_product_link' => 'https://example.com/product',
        'status' => 'approved',
        'target_audience' => 'General audience',
        'duration' => 'Campaign duration',
        'objectives' => 'Campaign objectives',
        'campaign_objective' => 'music_promotion',
        'metadata' => [
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
        ]
    ]);

    $signedUrl = URL::temporarySignedRoute('scan.dcd', now()->addHour(), ['dcd' => $dcd->id]);
    
    $response = $this->get($signedUrl);

    $response->assertRedirect('https://example.com/product');
    
    // Verify scan was recorded
    $scan = Scan::where('dcd_id', $dcd->id)->where('campaign_id', $campaign->id)->first();
    expect($scan)->not->toBeNull();
});

test('DCD scan shows error when no active campaigns', function () {
    $dcd = User::factory()->create(['role' => 'dcd']);
    
    // Create expired campaign
    Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'dcd_id' => $dcd->id,
        'title' => 'Expired Campaign',
        'description' => 'Already finished',
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
            'end_date' => Carbon::yesterday()->format('Y-m-d'),
        ]
    ]);

    $signedUrl = URL::temporarySignedRoute('scan.dcd', now()->addHour(), ['dcd' => $dcd->id]);
    
    $response = $this->get($signedUrl);

    $response->assertStatus(400);
    $response->assertSee('No active campaigns right now, try again later');
});

test('DCD scan rejects invalid signature', function () {
    $dcd = User::factory()->create(['role' => 'dcd']);
    
    // Create URL without signature
    $invalidUrl = route('scan.dcd', ['dcd' => $dcd->id]);
    
    $response = $this->get($invalidUrl);

    $response->assertStatus(400);
    $response->assertSee('Invalid or expired QR code');
});

test('DCD scan handles invalid DCD ID', function () {
    $signedUrl = URL::temporarySignedRoute('scan.dcd', now()->addHour(), ['dcd' => 999999]);
    
    $response = $this->get($signedUrl);

    $response->assertStatus(400);
    $response->assertSee('Invalid DCD code');
});

test('DCD scan prioritizes oldest campaign', function () {
    // Setup location
    $country = \App\Models\Country::create(['code' => 'KE', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);
    
    // Create older campaign first
    $olderCampaign = Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'dcd_id' => $dcd->id,
        'title' => 'Older Campaign',
        'description' => 'First campaign',
        'budget' => 100,
        'county' => 'Test County',
        'digital_product_link' => 'https://older-campaign.com',
        'status' => 'approved',
        'target_audience' => 'General audience',
        'duration' => 'Campaign duration',
        'objectives' => 'Campaign objectives',
        'campaign_objective' => 'music_promotion',
        'metadata' => [
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
        ]
    ]);

    sleep(1); // Ensure different timestamps

    // Create newer campaign
    Campaign::create([
        'client_id' => User::factory()->create(['role' => 'client'])->id,
        'dcd_id' => $dcd->id,
        'title' => 'Newer Campaign',
        'description' => 'Second campaign',
        'budget' => 200,
        'county' => 'Test County',
        'digital_product_link' => 'https://newer-campaign.com',
        'status' => 'approved',
        'target_audience' => 'General audience',
        'duration' => 'Campaign duration',
        'objectives' => 'Campaign objectives',
        'campaign_objective' => 'music_promotion',
        'metadata' => [
            'start_date' => Carbon::today()->format('Y-m-d'),
            'end_date' => Carbon::today()->addWeek()->format('Y-m-d'),
        ]
    ]);

    $signedUrl = URL::temporarySignedRoute('scan.dcd', now()->addHour(), ['dcd' => $dcd->id]);
    
    $response = $this->get($signedUrl);

    // Should redirect to older campaign
    $response->assertRedirect('https://older-campaign.com');
    
    // Verify scan was recorded for older campaign
    $scan = Scan::where('dcd_id', $dcd->id)->where('campaign_id', $olderCampaign->id)->first();
    expect($scan)->not->toBeNull();
});