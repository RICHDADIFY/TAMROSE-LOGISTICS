<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    // app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    $u = $request->user();

    return array_merge(parent::share($request), [
        'auth' => [
  'user' => fn () => $request->user() ? [
      'id'        => $request->user()->id,
      'name'      => $request->user()->name,
      'email'     => $request->user()->email,
      'roles'     => $request->user()->getRoleNames(),
      'is_manager'=> $request->user()->hasRole('Logistics Manager') || $request->user()->hasRole('Super Admin'),
      'is_driver' => $request->user()->hasRole('Driver'),
      'is_staff'  => $request->user()->hasRole('Staff'),
  ] : null,
],

        'config' => [
            'google' => ['mapsBrowserKey' => config('services.google.maps_browser_key')],
        ],
        'flash' => [
            'success' => fn () => session('success'),
            'error'   => fn () => session('error'),
        ],
         'invite' => fn () => $request->session()->get('invite'),
    ]);
}


}
