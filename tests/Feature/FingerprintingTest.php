<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('fingerprinting page loads correctly', function () {
    // Create test data
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);
    $user = \App\Models\User::factory()->create(['role' => 'dcd', 'ward_id' => $ward->id]);

    // Test the fingerprinting page loads
    $signedUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute('scan.dcd', now()->addYear(), ['dcd' => $user->id]);
    $response = $this->get($signedUrl);

    $response->assertStatus(200);
    $response->assertSee('Processing your scan');
    $response->assertSee('fingerprintjs');
});