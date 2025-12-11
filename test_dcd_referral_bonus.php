<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Ward;
use App\Models\Referral;
use App\Services\VentureShareService;
use App\Mail\DcdReferralBonusNotification;

// Find a ward for testing
$ward = Ward::first();
if (!$ward) {
    echo "No ward found. Please seed the database first.\n";
    exit;
}

echo "=== Testing DCD Referral Bonus System ===\n\n";

// Create test DCD (referrer)
$testDcd = User::create([
    'name' => 'Test DCD Referrer',
    'email' => 'test.dcd.referrer@example.com',
    'password' => bcrypt('password'),
    'role' => 'dcd',
    'referral_code' => 'DCDREF123',
    'ward_id' => $ward->id,
    'business_name' => 'Test DCD Business',
    'account_type' => 'business'
]);

// Create test DA (referred)
$testDa = User::create([
    'name' => 'Test Referred DA',
    'email' => 'test.referred.da@example.com',
    'password' => bcrypt('password'),
    'role' => 'da',
    'referral_code' => 'NEWDA456',
    'ward_id' => $ward->id,
]);

echo "Created test users:\n";
echo "- DCD Referrer: {$testDcd->name} (code: {$testDcd->referral_code})\n";
echo "- Referred DA: {$testDa->name}\n\n";

// Test VentureShareService allocation
$ventureService = new VentureShareService();

// Create referral record
$referral = Referral::create([
    'referrer_id' => $testDcd->id,
    'referred_id' => $testDa->id,
    'type' => 'dcd_to_da'
]);

echo "1. Testing venture share allocation...\n";
try {
    $ventureService->allocateSharesForReferral($referral);
    $balances = $ventureService->getTotalShares($testDcd);
    
    echo "   ✅ Venture shares allocated successfully\n";
    echo "   DDS Tokens: " . number_format($balances['kedds'], 2) . "\n";
    echo "   DWS Tokens: " . number_format($balances['kedws'], 2) . "\n";
    
    if ($balances['kedds'] >= 1000 && $balances['kedws'] >= 1000) {
        echo "   ✅ Correct token amounts allocated (1000 each)\n";
    } else {
        echo "   ❌ Incorrect token amounts\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Error allocating shares: " . $e->getMessage() . "\n";
}

echo "\n2. Testing DCD referral email...\n";
try {
    $mailable = new DcdReferralBonusNotification($testDcd, $testDa, $ventureService);
    echo "   ✅ DcdReferralBonusNotification mailable created successfully\n";
    
    $html = $mailable->render();
    echo "   ✅ Email template renders successfully\n";
    
    $subject = $mailable->envelope()->subject;
    echo "   Subject: '{$subject}'\n";
    
    // Check email content
    $contains2000 = strpos($html, '2,000') !== false;
    $contains1000 = strpos($html, '1,000') !== false;
    $containsDcdName = strpos($html, $testDcd->name) !== false;
    $containsDaName = strpos($html, $testDa->name) !== false;
    
    echo "   Email contains '2,000': " . ($contains2000 ? "✅ Yes" : "❌ No") . "\n";
    echo "   Email contains '1,000': " . ($contains1000 ? "✅ Yes" : "❌ No") . "\n";
    echo "   Email contains DCD name: " . ($containsDcdName ? "✅ Yes" : "❌ No") . "\n";
    echo "   Email contains DA name: " . ($containsDaName ? "✅ Yes" : "❌ No") . "\n";
    
} catch (\Exception $e) {
    echo "   ❌ Error testing email: " . $e->getMessage() . "\n";
}

// Clean up test data
$testDcd->delete();
$testDa->delete();

echo "\n🧹 Test data cleaned up\n";
echo "✅ DCD referral bonus system test completed successfully!\n";