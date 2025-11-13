<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DaController;
use App\Http\Controllers\Api\DcdController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CountriesController;
use App\Http\Controllers\Api\CountiesController;
use App\Http\Controllers\Api\SubcountiesController;
use App\Http\Controllers\Api\WardsController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ScanController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Public API routes for DDS system
Route::prefix('da')->group(function () {
    Route::post('/create', [DaController::class, 'create']);
});

Route::prefix('dcd')->group(function () {
    Route::post('/create', [DcdController::class, 'create']);
});

Route::prefix('client')->group(function () {
    Route::post('/campaign/submit', [ClientController::class, 'submitCampaign']);
});

Route::get('/countries', [CountriesController::class, 'index']);
Route::get('/counties', [CountiesController::class, 'index']);
Route::get('/subcounties', [SubcountiesController::class, 'index']);
Route::get('/wards', [WardsController::class, 'index']);

// Referral code validation
Route::post('/validate-referral', [AdminController::class, 'validateReferralCode']);
Route::get('/admin-referral-code', [AdminController::class, 'getAdminReferralCode']);

// Email validation
Route::post('/validate-email', [AdminController::class, 'validateEmail']);

// National ID validation
Route::post('/validate-national-id', [AdminController::class, 'validateNationalId']);

// Phone number validation
Route::post('/validate-phone', [AdminController::class, 'validatePhone']);

Route::prefix('admin')->group(function () {
    Route::post('/campaigns/{campaignId}/approve', [AdminController::class, 'approveCampaign']);
    Route::post('/campaigns/{campaignId}/complete', [AdminController::class, 'completeCampaign']);
    Route::get('/campaigns', [AdminController::class, 'getCampaigns']);
    Route::get('/venture-shares', [AdminController::class, 'getVentureSharesSummary']);
});

Route::prefix('scan')->group(function () {
    Route::post('/record', [ScanController::class, 'recordScan']);
    Route::get('/stats/admin', [ScanController::class, 'getAdminScanStats']);
    Route::get('/stats/user/{userId}', [ScanController::class, 'getScanStats']);
    Route::post('/qr/regenerate/{userId}', [ScanController::class, 'regenerateQRCode']);
    Route::get('/qr/{userId}', [ScanController::class, 'getQRCode']);
});