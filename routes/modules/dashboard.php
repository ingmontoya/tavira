<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// Dashboard
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Specific API routes for dashboard web interface
Route::prefix('api')->middleware(['auth'])->group(function () {
    // Debug auth route
    Route::get('/debug/auth', function () {
        return response()->json([
            'authenticated' => auth()->check(),
            'user' => auth()->user() ? auth()->user()->only(['id', 'name', 'email']) : null,
            'guard' => 'web',
        ]);
    });

    // Panic alerts for dashboard (web auth only)
    Route::prefix('dashboard')->group(function () {
        Route::get('/panic-alerts', [\App\Http\Controllers\Api\PanicAlertController::class, 'index']);
        Route::patch('/panic-alerts/{panicAlert}/resolve', [\App\Http\Controllers\Api\PanicAlertController::class, 'resolve']);
    });
});
