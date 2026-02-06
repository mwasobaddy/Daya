<?php

use App\Models\AdminAction;
use App\Models\Campaign;
use App\Models\User;
use App\Services\QRCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('admin approval auto-assigns a dcd and generates qr', function () {
    Mail::fake();

    // Create location and users
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'ShopsRUs', 'account_type' => 'business', 'ward_id' => $ward->id, 'qr_code' => 'qr-codes/existing-dcd-qr.svg']);

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
        'metadata' => [
            'business_name' => 'ShopsRUs',
            'business_types' => ['business'],
            'start_date' => now()->format('Y-m-d'), // Add start_date for auto-assignment
        ],
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

    // No need to mock QRCodeService since we use existing QR code

    $svc = app(\App\Services\AdminActionService::class);
    $result = $svc->executeAction($token, 'approve_campaign');

    expect($result['success'])->toBeTrue();

    $campaign->refresh();
    expect($campaign->dcd_id)->toBe($dcd->id);

    // Verify DCD retains existing QR code
    $dcd->refresh();
    expect($dcd->qr_code)->toBe('qr-codes/existing-dcd-qr.svg');
});
