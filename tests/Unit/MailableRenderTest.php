<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Mail\AdminDcdRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use App\Mail\DcdWelcome;
use App\Mail\ReferralBonusNotification;
use App\Services\VentureShareService;

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

// AdminCampaignSubmission test removed - using AdminCampaignPending instead

test('dcd welcome mailable renders successfully', function () {
    Storage::fake('public');

    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $referrer = User::factory()->create(['role' => 'da', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create([
        'role' => 'dcd', 
        'ward_id' => $ward->id,
        'email' => 'kelvinramsiel01@gmail.com'
    ]);

    // Set a referral code for testing
    $dcd->referral_code = 'TEST-REF-123';
    $dcd->save();

    // Create fake base64 PDF content
    $fakePdfBase64 = base64_encode('%PDF-1.4 fake pdf content');
    $dcd->qr_code = $fakePdfBase64;
    $dcd->save();

    $mailable = new DcdWelcome($dcd, $referrer, $fakePdfBase64);
    $html = $mailable->render();

    expect($html)->toContain('Your Role as a Digital Content Distributor');
    expect($html)->toContain('Your Personal QR Code');
    expect($html)->toContain('Attached to this email is your personal DCD QR code PDF');
    expect($html)->toContain('Your Referral Program');
    expect($html)->toContain('Earn additional income by referring new DCDs');
    expect($html)->toContain('TEST-REF-123'); // Check for the referral code
    expect($html)->toContain('/register?dcd_ref=TEST-REF-123'); // Check for the referral link
    
    $attachments = $mailable->attachments();
    expect($attachments)->toHaveCount(1);
    expect($attachments[0]->as)->toBe('dcd-qr-code.pdf');
});

test('dcd welcome mailable renders with fallback referral code', function () {
    Storage::fake('public');

    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $referrer = User::factory()->create(['role' => 'da', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create([
        'role' => 'dcd', 
        'ward_id' => $ward->id,
        'email' => 'kelvinramsiel01@gmail.com'
    ]);

    // Don't set referral_code to test fallback
    $fakePdfBase64 = base64_encode('%PDF-1.4 fake pdf content');
    $dcd->qr_code = $fakePdfBase64;
    $dcd->save();

    $mailable = new DcdWelcome($dcd, $referrer, $fakePdfBase64);
    $html = $mailable->render();

    expect($html)->toContain('Your Referral Program');
    expect($html)->toContain('DCD-' . $dcd->id); // Check for fallback referral code
    expect($html)->toContain('/register?dcd_ref=DCD-' . $dcd->id); // Check for fallback referral link
});

test('referral bonus notification mailable renders successfully', function () {
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $referrer = User::factory()->create([
        'role' => 'da', 
        'ward_id' => $ward->id,
        'country_id' => $country->id
    ]);

    $ventureShareService = app(VentureShareService::class);

    $mailable = new ReferralBonusNotification($referrer, $ventureShareService);
    $html = $mailable->render();

    expect($html)->toContain('Referral Bonus Update');
    expect($html)->toContain($referrer->name);
    expect($html)->toContain('KeDDS Tokens');
    expect($html)->toContain('KeDWS Tokens');
});

test('referral bonus notification mailable renders with Nigerian tokens', function () {
    $country = \App\Models\Country::create(['code' => 'ng', 'name' => 'Nigeria', 'county_label' => 'State', 'subcounty_label' => 'Local Government']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test State']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test LG']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    $referrer = User::factory()->create([
        'role' => 'da', 
        'ward_id' => $ward->id,
        'country_id' => $country->id
    ]);

    $ventureShareService = app(VentureShareService::class);

    $mailable = new ReferralBonusNotification($referrer, $ventureShareService);
    $html = $mailable->render();

    expect($html)->toContain('Referral Bonus Update');
    expect($html)->toContain($referrer->name);
    expect($html)->toContain('NgDDS Tokens');
    expect($html)->toContain('NgDWS Tokens');
});
