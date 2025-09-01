<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DriverLocationController;

// POST: driver publishes a ping (keep your custom throttle)
Route::middleware(['auth:sanctum', 'throttle:driver-pings'])->group(function () {
    Route::post('/trips/{trip}/locations', [DriverLocationController::class, 'store'])
        ->name('api.trips.locations.store');
});

// GET: UI reads recent points & ETA (no driver-pings throttle)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/trips/{trip}/recent-locations', [DriverLocationController::class, 'recent'])
        ->name('api.trips.locations.recent');

    Route::get('/trips/{trip}/eta', [DriverLocationController::class, 'eta'])
        ->name('api.trips.locations.eta');
});
