<?php

use App\Console\Commands\ScanAlerts;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Proactive alerts: daily 07:00
Schedule::command(ScanAlerts::class)->dailyAt('07:00');
