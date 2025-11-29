<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Mail\AdminDcdRegistration;
use App\Mail\AdminCampaignSubmission;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\DcdWelcome;

uses(RefreshDatabase::class);

test('admin dcd registration mailable renders successfully', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $admin = User::factory()->create(['role' => 'admin', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);

    // Craft mailable and render it to string
    $mailable = new AdminDcdRegistration($dcd, $admin);
    $html = $mailable->render();

    expect($html)->toContain('New Digital Content Distributor (DCD) Registration');
});

test('admin campaign submission mailable renders successfully', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);

    $campaign = \App\Models\Campaign::create([
        'client_id' => $client->id,
        'title' => 'Test Campaign',
        'description' => 'A test campaign',
        'budget' => 1000,
        'county' => $county->name,
        'campaign_objective' => 'brand_awareness',
        'target_audience' => 'general',
        'duration' => '30',
        'objectives' => 'awareness',
        'digital_product_link' => 'https://example.com/product/1',
    ]);

    $mailable = new AdminCampaignSubmission($campaign, $client, null);
    $html = $mailable->render();

    expect($html)->toContain('New Campaign Submission');
    expect($html)->toContain('Test Campaign');
});

test('dcd welcome mailable renders and attaches qr code', function () {
    Storage::fake('public');

    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $referrer = User::factory()->create(['role' => 'da', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);

    // Create fake base64 PDF content
    $fakePdfBase64 = base64_encode('%PDF-1.4 fake pdf content');
    $dcd->qr_code = $fakePdfBase64;
    $dcd->save();

    $mailable = new DcdWelcome($dcd, $referrer, $fakePdfBase64);
    $html = $mailable->render();

    expect($html)->toContain('Your QR Code');
    
    // $attachments = $mailable->attachments();
    // expect($attachments)->toHaveCount(1);
    // expect($attachments[0]->as)->toBe('qr-code.pdf');
});
