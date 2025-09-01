<?php

return [
    'guard' => 'web',
    'middleware' => ['web'],
    'views' => false, // ✅ We will render Inertia pages instead of Blade
    'features' => [
        Laravel\Fortify\Features::registration(),
        Laravel\Fortify\Features::resetPasswords(),
        Laravel\Fortify\Features::emailVerification(),
        Laravel\Fortify\Features::updateProfileInformation(),
        Laravel\Fortify\Features::updatePasswords(),
    ],
];
