<?php

use App\Models\AdminAction;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Services\CampaignMatchingService;
use App\Services\QRCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin approval auto-assigns a dcd and generates qr', function () {
    Mail::fake();

    // Create location and users
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
        'metadata' => ['business_name' => 'ShopsRUs', 'business_types' => ['business']],
    ]);

    // Make an adminAction record
    $token = Str::random(64);
    AdminAction::create([
        'action' => 'approve_campaign',
        'resource_type' => 'campaign',
        'resource_id' => $campaign->id,
        'token' => $token,
        'metadata' => [],
        'expires_at' => now()->addHour(),
    ]);

    // Mock QRCodeService to avoid file generation complexity.
    $mockQr = $this->mock(QRCodeService::class, function ($mock) use ($dcd, $campaign) {
        $mock->shouldReceive('generateDcdCampaignQr')->andReturn('qr-codes/test.svg');
    });

    $svc = app(\App\Services\AdminActionService::class);
    $result = $svc->executeAction($token, 'approve_campaign');

    expect($result['success'])->toBeTrue();

    $campaign->refresh();
    expect($campaign->dcd_id)->toBe($dcd->id);
    expect($campaign->metadata['dcd_qr'] ?? null)->not->toBeNull();
});
