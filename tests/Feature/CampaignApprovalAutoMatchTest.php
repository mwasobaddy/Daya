<?php

use App\Models\Campaign;
use App\Models\User;
use App\Models\Earning;
use App\Models\Scan;
use App\Services\AdminActionService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin approval auto-matches dcd, sends QR pdf, and scan leads to earning', function () {
    Mail::fake();

    // Setup location
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    // Create client and dcd
    $client = User::factory()->create(['role' => 'client', 'ward_id' => $ward->id]);
    $dcd = User::factory()->create([
        'role' => 'dcd',
        'business_name' => 'GenreDCD',
        'account_type' => 'business',
        'ward_id' => $ward->id,
        'country_id' => $country->id,
        'profile' => ['music_genres' => ['Afrobeats']],
        'qr_code' => 'qr-codes/test-dcd-qr.pdf',
    ]);

    // Create campaign in under_review state, with music genres that match the DCD
    $campaign = Campaign::create([
        'client_id' => $client->id,
        'title' => 'AutoMatch Campaign',
        'description' => 'Testing auto-match flow',
        'budget' => 200,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => '2025-12-07 to 2025-12-10',
        'objectives' => 'Test objectives',
        'campaign_objective' => 'music_promotion',
        'digital_product_link' => 'https://example.com',
        'status' => 'under_review',
        'metadata' => [
            'music_genres' => ['Afrobeats'],
            'target_country' => 'KEN',
            'pay_per_scan' => 1.23,
            'start_date' => '2025-12-07',
            'end_date' => '2025-12-10',
        ],
    ]);

    // Create an admin user (for the admin action route)
    $admin = User::factory()->create(['role' => 'admin', 'ward_id' => $ward->id]);

    // Generate action link and simulate clicking it
    $svc = app(AdminActionService::class);
    $url = $svc->generateActionLink('approve_campaign', $campaign->id);

    $response = $this->get($url);
    $response->assertStatus(200);

    $campaign = $campaign->fresh();
    expect($campaign->status)->toBe('approved');
    expect($campaign->dcd_id)->not->toBeNull();

    // Check that a CampaignApproved mail was sent to the matched DCD
    Mail::assertSent(\App\Mail\CampaignApproved::class, function ($mail) use ($dcd) {
        return $mail->hasTo($dcd->email);
    });

    // Now simulate a scan redirect (client scans the QR) and assert an Earning is created  
    // Now simulate a scan redirect (client scans the DCD QR) and assert an Earning is created
    $signedUrl = URL::temporarySignedRoute('scan.dcd', now()->addYear(), ['dcd' => $campaign->dcd_id]);
    $redirectResponse = $this->get($signedUrl);
    $redirectResponse->assertRedirect($campaign->digital_product_link);

    $scan = Scan::where('campaign_id', $campaign->id)->first();
    $earning = Earning::where('related_id', $scan->id)->where('type', 'scan')->first();
    expect($earning)->not->toBeNull();
    expect((float) $earning->amount)->toBe(1.23);
    expect($earning->user_id)->toBe($campaign->dcd_id);
});
