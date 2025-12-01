<?php

use App\Services\QRCodeService;
use App\Models\User;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

test('generate dcd campaign qr saves pdf to storage and returns filename', function () {
    Storage::fake('public');

    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'TestDcd', 'account_type' => 'business', 'ward_id' => $ward->id]);
    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

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
        'status' => 'approved',
        'metadata' => ['business_name' => 'TestDcd', 'business_types' => ['business']],
    ]);

    $svc = app(QRCodeService::class);
    $filename = $svc->generateDcdCampaignQr($dcd, $campaign);

    expect($filename)->toBeString();
    expect(Str::startsWith($filename, 'qrcodes/'))->toBeTrue();
    expect(Storage::disk('public')->exists($filename))->toBeTrue();
});

test('generate dcd qr saves pdf to storage and returns filename', function () {
    Storage::fake('public');

    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $dcd = User::factory()->create(['role' => 'dcd', 'business_name' => 'TestDcd', 'account_type' => 'business', 'ward_id' => $ward->id]);

    $svc = app(QRCodeService::class);
    $filename = $svc->generateDCDQRCode($dcd);

    expect($filename)->toBeString();
    expect(Str::startsWith($filename, 'qrcodes/'))->toBeTrue();
    expect(Storage::disk('public')->exists($filename))->toBeTrue();
    
    // Verify the user was updated with the QR code filename
    $dcd->refresh();
    expect($dcd->qr_code)->toBe($filename);
});
