<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule; // 👈 add this

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ✅ Nightly prune job for driver locations
Schedule::command('driver-locations:prune')
    ->dailyAt('02:30')
    ->withoutOverlapping();
