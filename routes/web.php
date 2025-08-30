<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        // your actual routes
        Route::get('/', function () {
            return Inertia::render('Welcome');
        })->name('home');

        // Include module route files outside of middleware groups
        require __DIR__ . '/modules/placeholder-modules.php';
        require __DIR__ . '/modules/subscription-payment.php';

        Route::middleware(['auth', 'verified'])->group(function () {
            // Central dashboard for tenant management
            require __DIR__ . '/modules/central-dashboard.php';
        });

        require __DIR__ . '/settings.php';
        require __DIR__ . '/auth.php';
    });
}
