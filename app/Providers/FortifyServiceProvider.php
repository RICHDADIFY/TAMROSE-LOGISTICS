<?php

namespace App\Providers;

use Inertia\Inertia;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use App\Actions\Auth\CreateNewUser; // we'll create this below

class FortifyServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Inertia pages for auth
        Fortify::loginView(fn () => Inertia::render('Auth/Login'));
        Fortify::registerView(fn () => Inertia::render('Auth/Register'));
        Fortify::verifyEmailView(fn () => Inertia::render('Auth/VerifyEmail'));
        Fortify::requestPasswordResetLinkView(fn () => Inertia::render('Auth/ForgotPassword'));
        Fortify::resetPasswordView(function ($request) {
            return Inertia::render('Auth/ResetPassword', [
                'email' => $request->email,
                'token' => $request->route('token'),
            ]);
        });

        // Custom user creation pipeline (handles avatar + invite codes)
        \Laravel\Fortify\Fortify::createUsersUsing(CreateNewUser::class);
    }
}
