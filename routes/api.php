<?php

use App\Http\Controllers\Api\FeaturesController;
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
        });

        // Protected central API routes (non-tenant specific)
        Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {
            // Central domain functionality only - tenant operations should be in tenant-api.php

            // Note: All tenant operational routes should be in tenant-api.php
            // This file should only contain central domain functionality
        });
    });
}
