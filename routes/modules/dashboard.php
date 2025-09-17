<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

// API routes for dashboard (using web auth)
Route::prefix('api')->name('api.')->middleware(['web'])->group(function () {
    // Panic alerts for dashboard
    Route::prefix('panic-alerts')->name('panic-alerts.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\PanicAlertController::class, 'index'])
            ->name('index');
        Route::patch('/{panicAlert}/resolve', [\App\Http\Controllers\Api\PanicAlertController::class, 'resolve'])
            ->name('resolve');
    });
});
