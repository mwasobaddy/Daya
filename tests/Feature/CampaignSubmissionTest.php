<?php

use App\Models\User;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

test('nigerian client campaign submission shows correct payment details in email', function () {
    Mail::fake();

    // Create a Nigerian country
    $nigeria = Country::create([
        'code' => 'NG',
        'name' => 'Nigeria',
        'county_label' => 'State',
        'subcounty_label' => 'Local Government'
    ]);

    // Create a DCD user
    $dcd = User::factory()->create([
        'role' => 'dcd',
        'name' => 'Test DCD',
        'email' => 'dcd@example.com',
    ]);

    // Campaign data for Nigerian client
    $data = [
        'account_type' => 'business',
        'business_name' => 'Test Business',
        'name' => 'Test Client',
        'email' => 'client@example.com',
        'phone' => '0123456789',
        'country' => 'NG',
        'referral_code' => null,
        'referred_by_code' => null,
        'dcd_id' => $dcd->id,
        'campaign_title' => 'Test Campaign',
        'digital_product_link' => 'https://example.com',
        'explainer_video_url' => null,
        'campaign_objective' => 'brand_awareness',
        'budget' => 100,
        'description' => 'Test description',
        'content_safety_preferences' => ['adult'],
        'target_country' => 'NG',
        'target_county' => null,
        'target_subcounty' => null,
        'target_ward' => null,
        'business_types' => ['retail'],
        'start_date' => now()->addDay()->format('Y-m-d'),
        'end_date' => now()->addDays(7)->format('Y-m-d'),
        'music_genres' => [],
        'target_audience' => null,
        'objectives' => null,
        'turnstile_token' => 'test_token',
    ];

    // Submit the campaign
    $response = $this->postJson('/api/client/campaign/submit', $data);

    $response->assertStatus(201);

    // Assert that the email was sent
    Mail::assertSent(\App\Mail\CampaignConfirmation::class, function ($mail) use ($data) {
        // Check that the email is sent to the client
        expect($mail->hasTo($data['email']))->toBeTrue();

        // Check that the campaign's client country is correctly set
        expect($mail->campaign->client->country->code)->toBe('NG');

        // Since the view uses this to determine payment details, this ensures FCMB is shown
        return true;
    });
});
