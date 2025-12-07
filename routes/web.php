<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::redirect('/login', '/');
Route::redirect('/register', '/');
Route::redirect('/dashboard', '/');
Route::redirect('/forgot-password', '/');
Route::redirect('/user/confirm-password', '/');
Route::redirect('/email/verify/{id}/{hash}', '/');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');
});

// Role selector demo (public)
Route::get('/role-demo', function () {
    return Inertia::render('role-demo');
})->name('role.demo');

// DDS Public Forms (no authentication required)
Route::get('/da/register', function () {
    return Inertia::render('dds/da-register');
})->name('dds.da.register');

Route::get('/dcd/register', function () {
    return Inertia::render('dds/dcd-register');
})->name('dds.dcd.register');

Route::get('/campaign/submit', function () {
    return Inertia::render('dds/campaign-submit');
})->name('dds.campaign.submit');

// Admin Action Routes (public - accessed via email links)
Route::get('/admin/action/{action}/{token}', [App\Http\Controllers\AdminActionController::class, 'handleAction'])
    ->name('admin.action');

// QR redirect (scan) routes - signed
Route::get('/qr/redirect', [\App\Http\Controllers\ScanRedirectController::class, 'handle'])
    ->name('scan.redirect'); // Legacy route for backward compatibility

// New DCD-specific scan route
Route::get('/scan/dcd/{dcd}', [\App\Http\Controllers\DCDScanController::class, 'handleDCDScan'])
    ->name('scan.dcd');

require __DIR__.'/settings.php';
