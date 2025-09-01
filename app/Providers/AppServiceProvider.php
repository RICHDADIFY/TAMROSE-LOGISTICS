<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;

use App\Models\TripRequest;
use App\Observers\TripRequestObserver;
use Inertia\Inertia;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Schema::defaultStringLength(191);
        TripRequest::observe(TripRequestObserver::class);

        // Share constants to all Inertia pages
        Inertia::share([
            'maps' => [
                // guard against missing keys
                'mode' => config('services.maps_mode', env('MAPS_MODE', 'off')),
                'key'  => config('services.google.maps_key', env('GOOGLE_MAPS_BROWSER_KEY')),
            ],
            'consts' => [
                // <-- this is from config/locations.php
                'onne' => config('locations.bases.Onne'),
            ],
        ]);

        // Rate limiter
        RateLimiter::for('driver-pings', function (Request $request) {
            $driverId = optional($request->user())->id ?? $request->input('driver_id');
            $tripId   = optional($request->route('trip'))->id ?? $request->route('trip');
            $key = sprintf('driver-pings:%s:%s', $driverId ?: 'guest', $tripId ?: 'none');

            return [
                Limit::perMinutes(1, 8)
                    ->by($key)
                    ->response(function () {
                        return response()->json([
                            'ok'       => false,
                            'accepted' => false,
                            'reason'   => 'rate_limited',
                            'message'  => 'Too many pings, slow down.',
                        ], 429);
                    }),

                Limit::perMinute(3)->by($key.':burst'),
            ];
        });
    }
}
