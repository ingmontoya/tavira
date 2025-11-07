<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    \Stancl\Tenancy\Middleware\ScopeSessions::class,
])->group(function () {
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });

    // Official tenancy impersonation route
    Route::get('/impersonate/{token}', function ($token) {
        return \Stancl\Tenancy\Features\UserImpersonation::makeResponse($token);
    });

    // Authentication routes for tenants (registration disabled)
    require __DIR__.'/tenant-auth.php';

    // Public PQRS routes (no authentication required)
    Route::get('/pqrs/public/create', [\App\Http\Controllers\PqrsController::class, 'publicCreate'])->name('pqrs.public.create');
    Route::post('/pqrs/public', [\App\Http\Controllers\PqrsController::class, 'store'])->name('pqrs.public.store');
    Route::get('/pqrs/public/success/{ticket}', [\App\Http\Controllers\PqrsController::class, 'publicSuccess'])->name('pqrs.public.success');
    Route::get('/pqrs/track', [\App\Http\Controllers\PqrsController::class, 'track'])->name('pqrs.track');

    Route::middleware(['auth', 'verified'])->group(function () {

        // Setup/Wizard routes
        Route::prefix('setup')->name('setup.')->group(function () {
            Route::get('accounting-wizard', [\App\Http\Controllers\Setup\AccountingWizardController::class, 'index'])
                ->name('accounting-wizard.index')
                ->middleware('can:manage_accounting');
            Route::post('accounting-wizard/quick-setup', [\App\Http\Controllers\Setup\AccountingWizardController::class, 'quickSetup'])
                ->name('accounting-wizard.quick-setup')
                ->middleware('can:manage_accounting');
        });

        // Tenant-specific module route files
        require __DIR__.'/modules/dashboard.php';
        require __DIR__.'/modules/reports.php';
        require __DIR__.'/modules/residents.php';
        require __DIR__.'/modules/conjunto-config.php';
        require __DIR__.'/modules/apartments.php';
        require __DIR__.'/modules/finance.php';
        require __DIR__.'/modules/accounting.php';
        require __DIR__.'/modules/communication.php';
        require __DIR__.'/modules/visits.php';
        require __DIR__.'/modules/users.php';
        require __DIR__.'/modules/maintenance.php';
        require __DIR__.'/modules/reservations.php';
        require __DIR__.'/modules/notifications.php';
        require __DIR__.'/modules/support.php';
        require __DIR__.'/modules/assemblies.php';
        require __DIR__.'/modules/security.php';
        require __DIR__.'/modules/pqrs.php';
    });

    // Settings routes for tenants
    require __DIR__.'/settings.php';

    // Debug routes (temporary - remove after testing)
    if (config('app.debug')) {
        require __DIR__.'/debug-overdue.php';
    }
});

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    // API routes for tenant mobile applications
    require __DIR__.'/tenant-api.php';
});
