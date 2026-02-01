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

// Schedule campaign recap emails to run twice daily (9 AM and 3 PM EAT)
Schedule::command('campaigns:send-recap-emails')
    ->twiceDaily(9, 15) // 9 AM and 3 PM
    ->timezone('Africa/Nairobi')
    ->onSuccess(function () {
        \Log::info('Campaign recap emails sent successfully');
    })
    ->onFailure(function () {
        \Log::error('Campaign recap emails failed to send');
    });

// Schedule campaign DCD matching to run daily at 10 AM EAT
Schedule::command('campaigns:match-unassigned')
    ->dailyAt('10:00')
    ->timezone('Africa/Nairobi')
    ->onSuccess(function () {
        \Log::info('Campaign DCD matching completed successfully');
    })
    ->onFailure(function () {
        \Log::error('Campaign DCD matching failed');
    });
