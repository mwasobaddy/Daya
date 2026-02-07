<?php

use App\Models\User;
use App\Models\Referral;
use App\Models\VentureShare;
use App\Services\VentureShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->ventureShareService = new VentureShareService();
});

test('DCD receives initial tokens when under cap', function () {
    // Create a DCD (count = 1, under 3000 cap)
    $dcd = User::factory()->create(['role' => 'dcd']);

    // Allocate initial tokens
    $this->ventureShareService->allocateInitialDcdTokens($dcd);

    // Verify tokens were allocated
    $shares = VentureShare::where('user_id', $dcd->id)->get();
    expect($shares)->toHaveCount(2);
    expect($shares->sum('kedds_amount'))->toBe(1000.0);
    expect($shares->sum('kedws_amount'))->toBe(1000.0);
});

test('DCD does not receive initial tokens when at cap', function () {
    // Create a service with DCD cap of 0 (simulating cap reached)
    $service = new VentureShareService(3000, 0);

    // Create one more DCD
    $newDcd = User::factory()->create(['role' => 'dcd']);

    // Try to allocate initial tokens
    $service->allocateInitialDcdTokens($newDcd);

    // Verify NO tokens were allocated
    $shares = VentureShare::where('user_id', $newDcd->id)->count();
    expect($shares)->toBe(0);
});

test('DA referral receives bonus when under cap', function () {
    // Create referrer DA and referred DA (count = 2, under 3000 cap)
    $referrerDa = User::factory()->create(['role' => 'da']);
    $referredDa = User::factory()->create(['role' => 'da']);

    // Create referral
    $referral = Referral::create([
        'referrer_id' => $referrerDa->id,
        'referred_id' => $referredDa->id,
        'type' => 'da_to_da',
    ]);

    // Allocate shares for referral
    $this->ventureShareService->allocateSharesForReferral($referral);

    // Verify referrer received bonus (200 DDS + 200 DWS)
    $shares = VentureShare::where('user_id', $referrerDa->id)->get();
    expect($shares)->toHaveCount(2);
    expect($shares->sum('kedds_amount'))->toBe(200.0);
    expect($shares->sum('kedws_amount'))->toBe(200.0);
});

test('DA referral does not receive bonus when at cap', function () {
    // Create a service with DA cap of 0 (simulating cap reached)
    $service = new VentureShareService(0, 3000);

    // Create referrer DA and referred DA
    $referrerDa = User::factory()->create(['role' => 'da']);
    $referredDa = User::factory()->create(['role' => 'da']);

    // Create referral
    $referral = Referral::create([
        'referrer_id' => $referrerDa->id,
        'referred_id' => $referredDa->id,
        'type' => 'da_to_da',
    ]);

    // Try to allocate shares for referral
    $service->allocateSharesForReferral($referral);

    // Verify NO shares were allocated to referrer
    $shares = VentureShare::where('user_id', $referrerDa->id)->count();
    expect($shares)->toBe(0);
});

test('DA referring DCD receives bonus when DCD under cap', function () {
    // Create DA and DCD
    $da = User::factory()->create(['role' => 'da']);
    $dcd = User::factory()->create(['role' => 'dcd']);

    // Create referral
    $referral = Referral::create([
        'referrer_id' => $da->id,
        'referred_id' => $dcd->id,
        'type' => 'da_to_dcd',
    ]);

    // Allocate shares for referral
    $this->ventureShareService->allocateSharesForReferral($referral);

    // Verify DA received bonus (500 DDS + 500 DWS)
    $shares = VentureShare::where('user_id', $da->id)->get();
    expect($shares)->toHaveCount(2);
    expect($shares->sum('kedds_amount'))->toBe(500.0);
    expect($shares->sum('kedws_amount'))->toBe(500.0);
});

test('DA referring DCD does not receive bonus when DCD at cap', function () {
    // Create a service with DCD cap of 0 (simulating cap reached)
    $service = new VentureShareService(3000, 0);

    // Create DA and new DCD
    $da = User::factory()->create(['role' => 'da']);
    $newDcd = User::factory()->create(['role' => 'dcd']);

    // Create referral
    $referral = Referral::create([
        'referrer_id' => $da->id,
        'referred_id' => $newDcd->id,
        'type' => 'da_to_dcd',
    ]);

    // Try to allocate shares for referral
    $service->allocateSharesForReferral($referral);

    // Verify NO shares were allocated to DA
    $shares = VentureShare::where('user_id', $da->id)->count();
    expect($shares)->toBe(0);
});

test('DCD referring DA receives bonus when DA under cap', function () {
    // Create DCD and DA
    $dcd = User::factory()->create(['role' => 'dcd']);
    $da = User::factory()->create(['role' => 'da']);

    // Create referral
    $referral = Referral::create([
        'referrer_id' => $dcd->id,
        'referred_id' => $da->id,
        'type' => 'dcd_to_da',
    ]);

    // Allocate shares for referral
    $this->ventureShareService->allocateSharesForReferral($referral);

    // Verify DCD received bonus (200 DDS + 200 DWS)
    $shares = VentureShare::where('user_id', $dcd->id)->get();
    expect($shares)->toHaveCount(2);
    expect($shares->sum('kedds_amount'))->toBe(200.0);
    expect($shares->sum('kedws_amount'))->toBe(200.0);
});

test('DCD referring DA does not receive bonus when DA at cap', function () {
    // Create a service with DA cap of 0 (simulating cap reached)
    $service = new VentureShareService(0, 3000);

    // Create DCD and new DA
    $dcd = User::factory()->create(['role' => 'dcd']);
    $newDa = User::factory()->create(['role' => 'da']);

    // Create referral
    $referral = Referral::create([
        'referrer_id' => $dcd->id,
        'referred_id' => $newDa->id,
        'type' => 'dcd_to_da',
    ]);

    // Try to allocate shares for referral
    $service->allocateSharesForReferral($referral);

    // Verify NO shares were allocated to DCD
    $shares = VentureShare::where('user_id', $dcd->id)->count();
    expect($shares)->toBe(0);
});

test('admin referring DA receives bonus when DA under cap', function () {
    // Create admin and DA
    $admin = User::factory()->create(['role' => 'admin']);
    $da = User::factory()->create(['role' => 'da']);

    // Create referral
    $referral = Referral::create([
        'referrer_id' => $admin->id,
        'referred_id' => $da->id,
        'type' => 'admin_to_da',
    ]);

    // Allocate shares for referral
    $this->ventureShareService->allocateSharesForReferral($referral);

    // Verify admin received bonus (200 DDS + 200 DWS)
    $shares = VentureShare::where('user_id', $admin->id)->get();
    expect($shares)->toHaveCount(2);
    expect($shares->sum('kedds_amount'))->toBe(200.0);
    expect($shares->sum('kedws_amount'))->toBe(200.0);
});

test('admin referring DA does not receive bonus when DA at cap', function () {
    // Create a service with DA cap of 0 (simulating cap reached)
    $service = new VentureShareService(0, 3000);

    // Create admin and new DA
    $admin = User::factory()->create(['role' => 'admin']);
    $newDa = User::factory()->create(['role' => 'da']);

    // Create referral
    $referral = Referral::create([
        'referrer_id' => $admin->id,
        'referred_id' => $newDa->id,
        'type' => 'admin_to_da',
    ]);

    // Try to allocate shares for referral
    $service->allocateSharesForReferral($referral);

    // Verify NO shares were allocated to admin
    $shares = VentureShare::where('user_id', $admin->id)->count();
    expect($shares)->toBe(0);
});
