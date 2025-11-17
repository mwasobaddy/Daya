<?php

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin action route for approve_campaign executes and returns success', function () {
    Mail::fake();

    // Create required location records
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'ShopsRUs', 'account_type' => 'business', 'ward_id' => $ward->id]);

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
        'status' => 'under_review',
    ]);

    // Generate signed link
    $svc = app(\App\Services\AdminActionService::class);
    $url = $svc->generateActionLink('approve_campaign', $campaign->id);

    // Make request
    $response = $this->get($url);

    $response->assertStatus(200);
    $response->assertSee('Campaign approved successfully');

    $campaign->refresh();
    expect($campaign->status)->toBe('approved');
});

test('admin action route returns error when link already used or invalid', function () {
    // Create required location records
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'title' => 'Test Campaign 2',
        'description' => 'Test description',
        'budget' => 100,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'submitted',
    ]);

    $svc = app(\App\Services\AdminActionService::class);
    $url = $svc->generateActionLink('approve_campaign', $campaign->id);

    // First hit - make a manual call to executeAction so it's used
    $token = parse_url($url, PHP_URL_PATH);
    // extract token from path - last segment
    $parts = explode('/', trim($token, '/'));
    $usedToken = end($parts);
    \App\Models\AdminAction::where('token', $usedToken)->first()->update(['used_at' => now()]);

    // Second hit - should return an error view
    $response = $this->get($url);
    $response->assertStatus(400);
    $response->assertSee('Action Failed');
});

test('campaign submission creates admin action link and clicking it approves campaign', function () {
    Mail::fake();

    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $admin = User::factory()->create(['role' => 'admin', 'ward_id' => $ward->id]);

    // POST to submit a campaign
    $payload = [
        'account_type' => 'business',
        'business_name' => 'Test Business',
        'name' => 'Client Name',
        'email' => 'client@example.org',
        'phone' => '0700111222',
        'country' => 'ken',
        'campaign_title' => 'Awesome Campaign',
        'digital_product_link' => 'https://example.com/item',
        'campaign_objective' => 'brand_awareness',
        'budget' => 300,
        'description' => 'Test description',
        'content_safety_preferences' => ['kids'],
        'target_country' => 'ken',
        'target_county' => $county->name,
        'target_subcounty' => $subcounty->name,
    'target_ward' => (string) $ward->id,
        'business_types' => ['business'],
        'start_date' => now()->addDay()->toDateString(),
        'end_date' => now()->addDays(5)->toDateString(),
        'target_audience' => 'General audience',
        'objectives' => 'Some objectives',
    ];

    $resp = $this->postJson('/api/client/campaign/submit', $payload);
    $resp->assertStatus(201);

    // Ensure AdminCampaignPending mailable was sent to the admin
    Mail::assertSent(\App\Mail\AdminCampaignPending::class, function ($mailable) use ($admin, &$approveUrl) {
        $approveUrl = $mailable->approveUrl ?? null;
        return !empty($approveUrl);
    });

    // Hit the approve URL
    $response = $this->get($approveUrl);
    $response->assertStatus(200);
    $response->assertSee('Campaign approved successfully');

    // When a DCD was auto-assigned, the success page should show assigned DCD details
    $response->assertSee('DCD Assigned');
});

test('admin action route returns error if campaign not under_review', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'title' => 'Test Campaign 3',
        'description' => 'Test description',
        'budget' => 100,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => '2025-11-17 to 2025-11-20',
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'submitted',
    ]);

    $svc = app(\App\Services\AdminActionService::class);
    $url = $svc->generateActionLink('approve_campaign', $campaign->id);

    $response = $this->get($url);
    $response->assertStatus(400);
    $response->assertSee('Campaign is not under review');
});
