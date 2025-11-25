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
        'county' => 'Example',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'None',
        'campaign_objective' => 'music_promotion',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
    ]);

    $url = URL::temporarySignedRoute('scan.redirect', now()->addYear(), ['dcd' => $dcd->id, 'campaign' => $campaign->id]);
    $response = $this->get($url);
    $response->assertRedirect($campaign->digital_product_link);

    $scan = \App\Models\Scan::where('campaign_id', $campaign->id)->first();
    $earning = Earning::where('related_id', $scan->id)->where('type', 'scan')->first();
    expect($earning)->not->toBeNull();
    expect((float)$earning->amount)->toBe(1.0);

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
        'county' => 'Example',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'None',
        'campaign_objective' => 'app_downloads',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
    ]);

    $url = URL::temporarySignedRoute('scan.redirect', now()->addYear(), ['dcd' => $dcd->id, 'campaign' => $campaign->id]);
    $this->get($url);

    $scan = \App\Models\Scan::where('campaign_id', $campaign->id)->first();
    $earning = Earning::where('related_id', $scan->id)->where('type', 'scan')->first();
    expect($earning)->not->toBeNull();
    expect((float)$earning->amount)->toBe(5.0);

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
        'county' => 'Example',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'None',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
    ]);

    $this->get(URL::temporarySignedRoute('scan.redirect', now()->addYear(), ['dcd' => $dcd->id, 'campaign' => $campaignSimple->id]));
    $scanSimple = \App\Models\Scan::where('campaign_id', $campaignSimple->id)->first();
    $earningSimple = Earning::where('related_id', $scanSimple->id)->where('type', 'scan')->first();
    expect((float)$earningSimple->amount)->toBe(1.0);
    $scanSimple = \App\Models\Scan::where('campaign_id', $campaignSimple->id)->first();
    expect((float) $scanSimple->earnings)->toBe(1.0);

    $campaignExplainer = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Brand - Explainer',
        'description' => 'BA Explainer',
        'budget' => 100,
        'county' => 'Example',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'None',
        'campaign_objective' => 'brand_awareness',
        'explainer_video_url' => 'https://youtube.com/vid',
        'digital_product_link' => 'https://example.com',
        'status' => 'approved',
    ]);

    $this->get(URL::temporarySignedRoute('scan.redirect', now()->addYear(), ['dcd' => $dcd->id, 'campaign' => $campaignExplainer->id]));
    $scanExplainer = \App\Models\Scan::where('campaign_id', $campaignExplainer->id)->first();
    $earningExplainer = Earning::where('related_id', $scanExplainer->id)->where('type', 'scan')->first();
    expect((float)$earningExplainer->amount)->toBe(5.0);
    $scanExplainer = \App\Models\Scan::where('campaign_id', $campaignExplainer->id)->first();
    expect((float) $scanExplainer->earnings)->toBe(5.0);
});
