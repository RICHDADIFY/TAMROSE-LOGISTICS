<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /** @var array<int, string> */
    protected $except = [
        // JSON endpoints called via fetch from the same SPA
        'consignments/*/prepare-delivery',
        'consignments/*/verify-otp',
        'trips/*/consignments/*/events',
    ];
}
