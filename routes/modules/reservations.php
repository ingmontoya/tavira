<?php

use App\Http\Controllers\ReservableAssetController;
use App\Http\Controllers\ReservationController;
use Illuminate\Support\Facades\Route;

// Reservation routes (require reservations feature)
Route::middleware('requires.feature:reservations')->group(function () {
    // Reservable Asset routes (admin only)
    Route::resource('reservable-assets', ReservableAssetController::class);

    // Reservation routes
    Route::resource('reservations', ReservationController::class);

    // Additional reservation routes
    Route::post('reservations/{reservation}/approve', [ReservationController::class, 'approve'])
        ->name('reservations.approve');
    Route::post('reservations/{reservation}/reject', [ReservationController::class, 'reject'])
        ->name('reservations.reject');
    Route::get('reservations/availability', [ReservationController::class, 'availability'])
        ->name('reservations.availability');
});
