<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use App\Http\Middleware\TrustProxies;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Run TrustProxies first for BOTH web and api
        $middleware->web(prepend: [TrustProxies::class]);
        $middleware->api(prepend: [TrustProxies::class]);

        // Sanctum should treat first-party requests as stateful on API routes
        $middleware->api(prepend: [EnsureFrontendRequestsAreStateful::class]);

         // ✅ Spatie role/permission aliases (v6 uses singular "Middleware")
      $middleware->alias([
          'role'               => \Spatie\Permission\Middleware\RoleMiddleware::class,
          'permission'         => \Spatie\Permission\Middleware\PermissionMiddleware::class,
          'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
      ]);

        // your existing web stack
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withProviders([
        \App\Providers\AppServiceProvider::class,
        \App\Providers\AuthServiceProvider::class,
    ])
    ->withCommands([
        \App\Console\Commands\GeocodeBackfillTripRequests::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
