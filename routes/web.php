<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        // your actual routes
        Route::get('/', function () {
            return Inertia::render('Welcome');
        })->name('home');

        // Marketing pages
        Route::get('/features', function () {
            return Inertia::render('Features');
        })->name('features');

        Route::get('/security', function () {
            return Inertia::render('Security');
        })->name('security.page');

        Route::get('/provider-register', [App\Http\Controllers\ProviderRegistrationController::class, 'create'])
            ->name('provider-register');

        Route::post('/provider-register', [App\Http\Controllers\ProviderRegistrationController::class, 'store'])
            ->name('provider-register.store');

        // Provider Password Setup (public routes)
        Route::get('/provider/password-setup/{token}', [App\Http\Controllers\ProviderPasswordSetupController::class, 'show'])
            ->name('provider.password.setup');

        Route::post('/provider/password-setup', [App\Http\Controllers\ProviderPasswordSetupController::class, 'store'])
            ->name('provider.password.store');

        // Include module route files outside of middleware groups
        require __DIR__.'/modules/placeholder-modules.php';
        require __DIR__.'/modules/subscription-payment.php';

        Route::middleware(['auth'])->group(function () {
            // Central dashboard for tenant management
            require __DIR__.'/modules/central-dashboard.php';

            // Provider Registration Management (Superadmin only)
            Route::prefix('admin/provider-registrations')->name('admin.provider-registrations.')->group(function () {
                Route::get('/', [App\Http\Controllers\ProviderRegistrationController::class, 'index'])->name('index');
                Route::get('/{registration}/edit', [App\Http\Controllers\ProviderRegistrationController::class, 'edit'])->name('edit');
                Route::put('/{registration}', [App\Http\Controllers\ProviderRegistrationController::class, 'update'])->name('update');
                Route::post('/{registration}/approve', [App\Http\Controllers\ProviderRegistrationController::class, 'approve'])->name('approve');
                Route::post('/{registration}/reject', [App\Http\Controllers\ProviderRegistrationController::class, 'reject'])->name('reject');
                Route::get('/{registration}', [App\Http\Controllers\ProviderRegistrationController::class, 'show'])->name('show');
            });

            // Central Provider Management (Superadmin only)
            Route::prefix('admin/providers')->name('admin.providers.')->group(function () {
                Route::get('/', [App\Http\Controllers\CentralProviderController::class, 'index'])->name('index');
                Route::get('/{provider}', [App\Http\Controllers\CentralProviderController::class, 'show'])->name('show');
                Route::get('/{provider}/edit', [App\Http\Controllers\CentralProviderController::class, 'edit'])->name('edit');
                Route::put('/{provider}', [App\Http\Controllers\CentralProviderController::class, 'update'])->name('update');
                Route::post('/{provider}/toggle-status', [App\Http\Controllers\CentralProviderController::class, 'toggleStatus'])->name('toggle-status');
                Route::delete('/{provider}', [App\Http\Controllers\CentralProviderController::class, 'destroy'])->name('destroy');
            });

            // Provider Routes (for authenticated providers)
            Route::middleware('role:provider')->prefix('provider')->name('provider.')->group(function () {
                // Dashboard
                Route::get('/dashboard', [App\Http\Controllers\Provider\ProviderDashboardController::class, 'index'])
                    ->name('dashboard');

                // Service Catalog Management
                Route::resource('services', App\Http\Controllers\Provider\ProviderServiceController::class);
                Route::post('/services/{service}/toggle-status', [App\Http\Controllers\Provider\ProviderServiceController::class, 'toggleStatus'])
                    ->name('services.toggle-status');

                // Quotation Requests
                Route::prefix('quotations')->name('quotations.')->group(function () {
                    Route::get('/', [App\Http\Controllers\Provider\ProviderQuotationController::class, 'index'])
                        ->name('index');
                    Route::get('/{tenantId}/{quotationRequestId}', [App\Http\Controllers\Provider\ProviderQuotationController::class, 'show'])
                        ->name('show');
                    Route::post('/{tenantId}/{quotationRequestId}/respond', [App\Http\Controllers\Provider\ProviderQuotationController::class, 'respond'])
                        ->name('respond');
                });

                // Proposals History
                Route::get('/proposals', [App\Http\Controllers\Provider\ProviderQuotationController::class, 'proposals'])
                    ->name('quotations.proposals');
            });
        });

        require __DIR__.'/settings.php';
        require __DIR__.'/auth.php';
    });
}
