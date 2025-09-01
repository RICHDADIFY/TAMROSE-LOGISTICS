<?php

namespace App\Providers;

use App\Models\TripRequest;
use App\Policies\TripRequestPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Trip;
use App\Policies\TripPolicy;


class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        TripRequest::class => TripRequestPolicy::class,
        Trip::class        => TripPolicy::class,    // 👈 NEW
    ];

    

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return ($user->is_super_admin ?? false) ? true : null;
        });

        Gate::define('manage-vehicles', fn ($user) => (bool)($user->is_manager ?? false));
    }
}
