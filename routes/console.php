<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule daily admin digest to run every day at 8:00 AM East Africa Time (EAT)
Schedule::command('digest:send-admin-daily')
    ->dailyAt('08:00')
    ->timezone('Africa/Nairobi')
    ->onSuccess(function () {
        \Log::info('Daily admin digest sent successfully');
    })
    ->onFailure(function () {
        \Log::error('Daily admin digest failed to send');
    });
