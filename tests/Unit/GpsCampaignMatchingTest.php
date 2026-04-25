<?php

use App\Models\Campaign;
use App\Models\User;
use App\Services\QRCodeService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// =====================================================================
// Helper: create DCD + client + geo hierarchy
// =====================================================================
function createGeoHierarchy(): array
{
    $country = \App\Models\Country::create(['code' => 'ken', 'name' => 'Kenya', 'county_label' => 'County', 'subcounty_label' => 'Subcounty']);
    $county = \App\Models\County::create(['country_id' => $country->id, 'name' => 'Test County']);
    $subcounty = \App\Models\Subcounty::create(['county_id' => $county->id, 'name' => 'Test Subcounty']);
    $ward = \App\Models\Ward::create(['subcounty_id' => $subcounty->id, 'name' => 'Test Ward', 'code' => 'TW']);

    return compact('country', 'county', 'subcounty', 'ward');
}

function createDcd(array $geo = []): User
{
    $ward = $geo['ward'] ?? \App\Models\Ward::first()
        ?? \App\Models\Ward::factory()->create();

    return User::factory()->create([
        'role' => 'dcd',
        'business_name' => 'TestDcd',
        'account_type' => 'business',
        'ward_id' => $ward->id,
    ]);
}

function createClient(array $geo = []): User
{
    $ward = $geo['ward'] ?? \App\Models\Ward::first()
        ?? \App\Models\Ward::factory()->create();

    return User::factory()->create([
        'role' => 'client',
        'ward_id' => $ward->id,
    ]);
}

function activeCampaignParams(string $businessName = 'TestDcd'): array
{
    $today = now()->format('Y-m-d');
    $tomorrow = now()->addDay()->format('Y-m-d');

    return [
        'budget' => 100,
        'campaign_credit' => 100,
        'county' => 'Example County',
        'target_audience' => 'General Audience',
        'duration' => "$today to $tomorrow",
        'objectives' => 'Test objectives',
        'campaign_objective' => 'brand_awareness',
        'digital_product_link' => 'https://example.com',
        'status' => 'live',
        'metadata' => [
            'business_name' => $businessName,
            'business_types' => ['business'],
            'start_date' => $today,
            'end_date' => $tomorrow,
        ],
    ];
}

// =====================================================================
// Test 1: Without GPS — non-location campaign is selected via fairness
// =====================================================================
test('no gps — selects non-location campaign via fairness algorithm', function () {
    $geo = createGeoHierarchy();
    $dcd = createDcd($geo);
    $client = createClient($geo);

    $campaign = Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Non-Location Campaign',
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
                // NOTE: no location_latitude / location_longitude
            ],
        ]
    ));

    $svc = app(QRCodeService::class);
    $result = $svc->recordDcdScan($dcd->id, null);

    expect($result['campaign']->id)->toBe($campaign->id);
    expect($result['campaign']->title)->toBe('Non-Location Campaign');
});

// =====================================================================
// Test 2: With GPS — location-based campaign is selected nearest first
// =====================================================================
test('with gps — selects nearest location-based campaign', function () {
    $geo = createGeoHierarchy();
    $dcd = createDcd($geo);
    $client = createClient($geo);

    // Campaign A — far away (Nairobi CBD ~ -1.2833, 36.8167)
    Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Far Campaign',
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
                'location_latitude' => -1.2833,
                'location_longitude' => 36.8167,
            ],
        ]
    ));

    // Campaign B — near the passenger (~100m away)
    Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Near Campaign',
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
                'location_latitude' => -1.265,
                'location_longitude' => 36.802,
            ],
        ]
    ));

    // Non-location campaign (should be ignored in GPS path)
    Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Non-Location Campaign',
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
                // no location coords
            ],
        ]
    ));

    // Passenger GPS: near Westlands area (~ -1.264, 36.802)
    $geoData = [
        'latitude' => -1.264,
        'longitude' => 36.802,
        'accuracy' => 50,
    ];

    $svc = app(QRCodeService::class);
    $result = $svc->recordDcdScan($dcd->id, $geoData);

    // Should match the NEAR Campaign (not the far one, not the non-location one)
    expect($result['campaign']->title)->toBe('Near Campaign');
});

// =====================================================================
// Test 3: With GPS — nearest wins even if further campaign has less scans
// =====================================================================
test('with gps — nearest campaign wins over fairness (fewer scans)', function () {
    $geo = createGeoHierarchy();
    $dcd = createDcd($geo);
    $client = createClient($geo);

    // Campaign with fewer scans but far away
    Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Far But Low Scans',
            'total_scans' => 0,
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
                'location_latitude' => -1.300,
                'location_longitude' => 36.850,
            ],
        ]
    ));

    // Campaign with more scans but very near
    Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Near But More Scans',
            'total_scans' => 50,
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
                'location_latitude' => -1.265,
                'location_longitude' => 36.802,
            ],
        ]
    ));

    // Passenger GPS: near Westlands
    $geoData = [
        'latitude' => -1.264,
        'longitude' => 36.802,
        'accuracy' => 20,
    ];

    $svc = app(QRCodeService::class);
    $result = $svc->recordDcdScan($dcd->id, $geoData);

    // Location wins — should pick nearest despite having more scans
    expect($result['campaign']->title)->toBe('Near But More Scans');
});

// =====================================================================
// Test 4: With GPS — budget-exhausted nearest is skipped, next nearest chosen
// =====================================================================
test('with gps — skips budget-exhausted nearest, picks next nearest', function () {
    $geo = createGeoHierarchy();
    $dcd = createDcd($geo);
    $client = createClient($geo);

    // Nearest but budget exhausted
    Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Nearest But Exhausted',
            'budget' => 100,
            'spent_amount' => 100, // fully spent
            'campaign_credit' => 0,
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
                'location_latitude' => -1.265,
                'location_longitude' => 36.802,
            ],
        ]
    ));

    // Slightly further but has budget
    Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Second Nearest With Budget',
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
                'location_latitude' => -1.268,
                'location_longitude' => 36.805,
            ],
        ]
    ));

    $geoData = [
        'latitude' => -1.264,
        'longitude' => 36.802,
        'accuracy' => 20,
    ];

    $svc = app(QRCodeService::class);
    $result = $svc->recordDcdScan($dcd->id, $geoData);

    expect($result['campaign']->title)->toBe('Second Nearest With Budget');
});

// =====================================================================
// Test 5: With GPS but all location campaigns exhausted — falls to fairness
// =====================================================================
test('with gps but all location campaigns exhausted — falls to fairness, includes non-location', function () {
    $geo = createGeoHierarchy();
    $dcd = createDcd($geo);
    $client = createClient($geo);

    // Location campaign — budget exhausted
    Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Exhausted Location Campaign',
            'budget' => 100,
            'spent_amount' => 100,
            'campaign_credit' => 0,
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
                'location_latitude' => -1.265,
                'location_longitude' => 36.802,
            ],
        ]
    ));

    // Non-location campaign with budget
    Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Non-Location With Budget',
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
                // no location coords
            ],
        ]
    ));

    $geoData = [
        'latitude' => -1.264,
        'longitude' => 36.802,
        'accuracy' => 20,
    ];

    $svc = app(QRCodeService::class);
    $result = $svc->recordDcdScan($dcd->id, $geoData);

    // Should fall through to fairness and pick the non-location campaign
    expect($result['campaign']->title)->toBe('Non-Location With Budget');
});

// =====================================================================
// Test 6: With GPS — location campaign selected even if device already earned from it
// (Reward dedup is handled downstream by ScanRewardService)
// =====================================================================
test('with gps — matches nearest location campaign regardless of prior earnings', function () {
    $geo = createGeoHierarchy();
    $dcd = createDcd($geo);
    $client = createClient($geo);

    // Create a campaign with location coords
    $campaign = Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Location Campaign',
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
                'location_latitude' => -1.265,
                'location_longitude' => 36.802,
            ],
        ]
    ));

    // Simulate a prior earning for this device on this campaign
    $deviceFingerprint = 'test-fingerprint-123';
    $priorScan = \App\Models\Scan::create([
        'dcd_id' => $dcd->id,
        'campaign_id' => $campaign->id,
        'scanned_at' => now()->subHour(),
        'device_fingerprint' => $deviceFingerprint,
        'geo' => ['latitude' => -1.264, 'longitude' => 36.802, 'ip_address' => '127.0.0.1'],
    ]);
    \App\Models\Earning::create([
        'type' => 'scan',
        'scan_id' => $priorScan->id,
        'campaign_id' => $campaign->id,
        'user_id' => $client->id,
        'amount' => 10,
        'currency' => 'KES',
        'device_fingerprint' => $deviceFingerprint,
        'earned_at' => now(),
    ]);

    // Also create a non-location campaign
    Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Non-Location Campaign',
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
            ],
        ]
    ));

    $geoData = [
        'latitude' => -1.264,
        'longitude' => 36.802,
        'accuracy' => 20,
        'fingerprint' => $deviceFingerprint,
    ];

    $svc = app(QRCodeService::class);
    $result = $svc->recordDcdScan($dcd->id, $geoData);

    // Location always wins — should still match the location campaign
    // even though this device already earned from it
    expect($result['campaign']->title)->toBe('Location Campaign');
});

// =====================================================================
// Test 7: No GPS, all campaigns already earned from — random fallback
// =====================================================================
test('no gps — all campaigns already earned from, random fallback', function () {
    $geo = createGeoHierarchy();
    $dcd = createDcd($geo);
    $client = createClient($geo);

    $campaign = Campaign::create(array_merge(
        activeCampaignParams('TestDcd'),
        [
            'client_id' => $client->id,
            'dcd_id' => $dcd->id,
            'title' => 'Test Campaign',
            'metadata' => [
                'business_name' => 'TestDcd',
                'business_types' => ['business'],
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDay()->format('Y-m-d'),
            ],
        ]
    ));

    // Create a prior earning for this device
    $deviceFingerprint = 'test-fp';
    $priorScan = \App\Models\Scan::create([
        'dcd_id' => $dcd->id,
        'campaign_id' => $campaign->id,
        'scanned_at' => now()->subHour(),
        'device_fingerprint' => $deviceFingerprint,
        'geo' => ['ip_address' => '127.0.0.1'],
    ]);
    \App\Models\Earning::create([
        'type' => 'scan',
        'scan_id' => $priorScan->id,
        'campaign_id' => $campaign->id,
        'user_id' => $client->id,
        'amount' => 10,
        'currency' => 'KES',
        'device_fingerprint' => $deviceFingerprint,
        'earned_at' => now(),
    ]);

    $geoData = [
        'fingerprint' => 'test-fp',
    ];

    $svc = app(QRCodeService::class);
    $result = $svc->recordDcdScan($dcd->id, $geoData);

    // Without GPS and all earned-from, it uses random fallback among all active
    expect($result['campaign']->id)->toBe($campaign->id);
});

// =====================================================================
// Test 8: Distance calculation is correct — closest is within reasonable range
// =====================================================================
test('calculate distance returns reasonable values', function () {
    // Use reflection to test the private method
    $svc = app(QRCodeService::class);
    $reflection = new ReflectionClass($svc);
    $method = $reflection->getMethod('calculateDistanceMeters');
    $method->setAccessible(true);

    // Same point
    $zeroDist = $method->invokeArgs($svc, [-1.264, 36.802, -1.264, 36.802]);
    expect($zeroDist)->toBe(0.0);

    // ~1km apart (roughly)
    $oneKmDist = $method->invokeArgs($svc, [-1.264, 36.802, -1.270, 36.810]);
    expect($oneKmDist)->toBeGreaterThan(500);
    expect($oneKmDist)->toBeLessThan(1500);

    // ~10km apart
    $tenKmDist = $method->invokeArgs($svc, [-1.264, 36.802, -1.300, 36.850]);
    expect($tenKmDist)->toBeGreaterThan(5000);
    expect($tenKmDist)->toBeLessThan(15000);
});
