<?php

use App\Models\User;
use App\Models\Campaign;
use App\Models\Scan;
use App\Models\Earning;
use App\Models\Referral;
use App\Services\ScanRewardService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('it credits dcd earnings for light touch campaign', function () {
    $scanRewardService = app(ScanRewardService::class);
    
    $client = User::factory()->create(['role' => 'client']);
    $dcd = User::factory()->create(['role' => 'dcd']);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Test Music Campaign',
        'budget' => 100,
        'cost_per_click' => 1.0,
        'spent_amount' => 0,
        'max_scans' => 100,
        'total_scans' => 0,
        'county' => 'Test County',
        'status' => 'approved',
        'campaign_objective' => 'music_promotion',
        'digital_product_link' => 'https://example.com',
        'target_audience' => 'General audience',
        'duration' => '2026-01-10 to 2026-01-20',
        'objectives' => 'Test objectives',
        'metadata' => [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ],
    ]);

    $scan = Scan::create([
        'dcd_id' => $dcd->id,
        'campaign_id' => $campaign->id,
        'scanned_at' => now(),
    ]);

    $earning = $scanRewardService->creditScanReward($scan);

    expect($earning)->not->toBeNull()
        ->and($earning->amount)->toBe(1.0)
        ->and($earning->type)->toBe('scan_earning')
        ->and($earning->user_id)->toBe($dcd->id);
    
    $campaign->refresh();
    expect($campaign->total_scans)->toBe(1)
        ->and($campaign->spent_amount)->toBe(1.0);
});

test('it credits dcd earnings for moderate touch campaign', function () {
    $scanRewardService = app(ScanRewardService::class);
    $client = User::factory()->create(['role' => 'client']);
    $dcd = User::factory()->create(['role' => 'dcd']);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Test App Campaign',
        'budget' => 500,
        'cost_per_click' => 5.0,
        'spent_amount' => 0,
        'max_scans' => 100,
        'total_scans' => 0,
        'county' => 'Test County',
        'status' => 'approved',
        'campaign_objective' => 'app_downloads',
        'digital_product_link' => 'https://example.com',
        'target_audience' => 'General audience',
        'duration' => '2026-01-10 to 2026-01-20',
        'objectives' => 'Test objectives',
        'metadata' => [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ],
    ]);

    $scan = Scan::create([
        'dcd_id' => $dcd->id,
        'campaign_id' => $campaign->id,
        'scanned_at' => now(),
    ]);

    $earning = $scanRewardService->creditScanReward($scan);

    expect($earning)->not->toBeNull()
        ->and($earning->amount)->toBe(5.0)
        ->and($earning->type)->toBe('scan_earning');
});

test('it credits da commission when dcd is referred', function () {
    $scanRewardService = app(ScanRewardService::class);
    
    $da = User::factory()->create([
        'role' => 'da',
        'referral_code' => 'TEST123',
    ]);

    $dcd = User::factory()->create(['role' => 'dcd']);

    Referral::create([
        'referrer_id' => $da->id,
        'referred_id' => $dcd->id,
        'referral_type' => 'da_to_dcd',
    ]);

    $client = User::factory()->create(['role' => 'client']);
    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Test Campaign',
        'budget' => 1000,
        'cost_per_click' => 5.0,
        'spent_amount' => 0,
        'max_scans' => 200,
        'total_scans' => 0,
        'county' => 'Test County',
        'status' => 'approved',
        'campaign_objective' => 'app_downloads',
        'digital_product_link' => 'https://example.com',
        'target_audience' => 'General audience',
        'duration' => '2026-01-10 to 2026-01-20',
        'objectives' => 'Test objectives',
        'metadata' => [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ],
    ]);

    $scan = Scan::create([
        'dcd_id' => $dcd->id,
        'campaign_id' => $campaign->id,
        'scanned_at' => now(),
    ]);

    $dcdEarning = $scanRewardService->creditScanReward($scan);

    expect($dcdEarning)->not->toBeNull()
        ->and($dcdEarning->amount)->toBe(5.0);

    $daEarning = Earning::where('user_id', $da->id)
        ->where('type', 'commission')
        ->first();

    expect($daEarning)->not->toBeNull()
        ->and($daEarning->amount)->toBe(0.25)
        ->and($daEarning->campaign_id)->toBe($scan->campaign_id)
        ->and($daEarning->scan_id)->toBe($scan->id);
});

test('it prevents duplicate scan rewards', function () {
    $scanRewardService = app(ScanRewardService::class);
    $client = User::factory()->create(['role' => 'client']);
    $dcd = User::factory()->create(['role' => 'dcd']);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Test Campaign',
        'budget' => 100,
        'cost_per_click' => 1.0,
        'spent_amount' => 0,
        'max_scans' => 100,
        'total_scans' => 0,
        'county' => 'Test County',
        'status' => 'approved',
        'campaign_objective' => 'music_promotion',
        'digital_product_link' => 'https://example.com',
        'target_audience' => 'General audience',
        'duration' => '2026-01-10 to 2026-01-20',
        'objectives' => 'Test objectives',
        'metadata' => [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ],
    ]);

    $scan = Scan::create([
        'dcd_id' => $dcd->id,
        'campaign_id' => $campaign->id,
        'scanned_at' => now(),
    ]);

    $earning1 = $scanRewardService->creditScanReward($scan);
    $earning2 = $scanRewardService->creditScanReward($scan);

    expect($earning1)->not->toBeNull()
        ->and($earning2)->toBeNull();

    $earningCount = Earning::where('scan_id', $scan->id)
        ->where('type', 'scan_earning')
        ->count();
    expect($earningCount)->toBe(1);
});

test('it auto completes campaign when budget exhausted', function () {
    $scanRewardService = app(ScanRewardService::class);
    $client = User::factory()->create(['role' => 'client']);
    $dcd = User::factory()->create(['role' => 'dcd']);

    $campaign = Campaign::create([
        'client_id' => $client->id,
        'dcd_id' => $dcd->id,
        'title' => 'Test Campaign',
        'budget' => 10,
        'cost_per_click' => 5.0,
        'spent_amount' => 0,
        'max_scans' => 2,
        'total_scans' => 0,
        'county' => 'Test County',
        'status' => 'approved',
        'campaign_objective' => 'app_downloads',
        'digital_product_link' => 'https://example.com',
        'target_audience' => 'General audience',
        'duration' => '2026-01-10 to 2026-01-20',
        'objectives' => 'Test objectives',
        'metadata' => [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ],
    ]);

    $scan1 = Scan::create([
        'dcd_id' => $dcd->id,
        'campaign_id' => $campaign->id,
        'scanned_at' => now(),
    ]);
    $scanRewardService->creditScanReward($scan1);

    $campaign->refresh();
    expect($campaign->status)->toBe('live')
        ->and($campaign->spent_amount)->toBe(5.0);

    $scan2 = Scan::create([
        'dcd_id' => $dcd->id,
        'campaign_id' => $campaign->id,
        'scanned_at' => now(),
    ]);
    $scanRewardService->creditScanReward($scan2);

    $campaign->refresh();
    expect($campaign->status)->toBe('completed')
        ->and($campaign->spent_amount)->toBe(10.0)
        ->and($campaign->completed_at)->not->toBeNull();
});

