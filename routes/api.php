<?php

use App\Http\Controllers\Api\ConjuntoSearchController;
use App\Http\Controllers\Api\DeviceTokenController;
use App\Http\Controllers\Api\FeaturesController;
use App\Http\Controllers\Api\PoliceAlertsController;
use App\Http\Controllers\Api\ProviderRegistrationController;
use App\Http\Controllers\Api\SecurityAuthController;
use App\Http\Controllers\Api\SecurityRegistrationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your mobile application.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        // Public API routes for mobile app registration (no authentication required)
        Route::prefix('conjuntos')->middleware(['throttle:30,1'])->group(function () {
            Route::get('/search', [ConjuntoSearchController::class, 'search'])
                ->name('api.conjuntos.search');
            Route::get('/{subdomain}/estructura', [ConjuntoSearchController::class, 'getStructure'])
                ->name('api.conjuntos.structure');
            Route::get('/{subdomain}/validate', [ConjuntoSearchController::class, 'validateSubdomain'])
                ->name('api.conjuntos.validate');
        });

        // Internal API routes for inter-app communication (central <-> tenant)
        Route::prefix('internal')->middleware(['throttle:300,1'])->group(function () {
            // Feature flags API for tenant apps
            Route::get('/features/{tenant}', [FeaturesController::class, 'index'])
                ->name('api.internal.features.index');
            Route::get('/features/{tenant}/{feature}', [FeaturesController::class, 'show'])
                ->name('api.internal.features.show');
        });

        // Authentication routes for mobile app
        Route::prefix('')->group(function () {
            // Login
            Route::post('/login', [AuthenticatedSessionController::class, 'store'])
                ->middleware(['guest', 'throttle:6,1'])
                ->name('api.login');

            // Logout (requires authentication)
            Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
                ->middleware(['auth:sanctum'])
                ->name('api.logout');

            // Get authenticated user
            Route::get('/user', function (Request $request) {
                $user = $request->user()->load(['resident.apartment.apartmentType', 'roles']);

                // Add the first role name as 'role' field for mobile compatibility
                $userData = $user->toArray();
                $userData['role'] = $user->roles->first()?->name ?? 'residente';

                return response()->json([
                    'user' => $userData,
                ]);
            })->middleware(['auth:sanctum'])->name('api.user');

            // Security personnel registration and authentication (police, security companies, etc.)
            Route::prefix('auth/security')->middleware(['throttle:6,1'])->group(function () {
                // Registration
                Route::post('/register', [SecurityRegistrationController::class, 'store'])
                    ->name('api.auth.security.register');
                Route::post('/verify-email', [SecurityRegistrationController::class, 'verifyEmail'])
                    ->name('api.auth.security.verify-email');
                Route::post('/resend-verification', [SecurityRegistrationController::class, 'resendVerification'])
                    ->name('api.auth.security.resend-verification');

                // Authentication
                Route::post('/login', [SecurityAuthController::class, 'login'])
                    ->name('api.auth.security.login');
                Route::post('/logout', [SecurityAuthController::class, 'logout'])
                    ->name('api.auth.security.logout');
                Route::post('/check-status', [SecurityAuthController::class, 'checkStatus'])
                    ->name('api.auth.security.check-status');

                // Protected routes for security personnel
                Route::middleware(['auth:sanctum'])->group(function () {
                    Route::get('/me', [SecurityAuthController::class, 'me'])
                        ->name('api.auth.security.me');
                });
            });

            // Provider registration (service providers for residential complexes)
            Route::prefix('provider-register')->middleware(['throttle:6,1'])->group(function () {
                Route::get('/categories', [ProviderRegistrationController::class, 'categories'])
                    ->name('api.provider-register.categories');
                Route::post('/', [ProviderRegistrationController::class, 'store'])
                    ->name('api.provider-register.store');
            });
        });

        // Protected central API routes (non-tenant specific)
        Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
            // Device tokens - stored centrally for cross-tenant notifications
            Route::prefix('device-tokens')->group(function () {
                Route::get('/', [DeviceTokenController::class, 'index'])
                    ->name('api.device-tokens.index');
                Route::post('/', [DeviceTokenController::class, 'store'])
                    ->name('api.device-tokens.store');
                Route::post('/location', [DeviceTokenController::class, 'updateLocation'])
                    ->name('api.device-tokens.update-location');
                Route::delete('/{token}', [DeviceTokenController::class, 'destroy'])
                    ->name('api.device-tokens.destroy');
                Route::post('/deactivate-all', [DeviceTokenController::class, 'deactivateAll'])
                    ->name('api.device-tokens.deactivate-all');
            });

            // Police alerts - cross-tenant access for SecurityPersonnel
            Route::prefix('police')->group(function () {
                Route::get('/alerts', [PoliceAlertsController::class, 'index'])
                    ->name('api.police.alerts');
                Route::get('/stats', [PoliceAlertsController::class, 'stats'])
                    ->name('api.police.stats');
                Route::post('/alerts/respond', [PoliceAlertsController::class, 'respond'])
                    ->name('api.police.alerts.respond');
                Route::post('/alerts/resolve', [PoliceAlertsController::class, 'resolve'])
                    ->name('api.police.alerts.resolve');
            });
        });
    });
}
