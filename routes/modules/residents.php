<?php

use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ResidentDashboardController;
use Illuminate\Support\Facades\Route;

// Resident Dashboard - accessible to residents
Route::get('resident/dashboard', [ResidentDashboardController::class, 'index'])->name('resident.dashboard');

// Residents Management - admin only
Route::resource('residents', ResidentController::class)->middleware('can:view_residents');

// Resident approval/rejection routes
Route::post('residents/{resident}/approve', [ResidentController::class, 'approve'])
    ->name('residents.approve')
    ->middleware('can:view_residents');

Route::post('residents/{resident}/reject', [ResidentController::class, 'reject'])
    ->name('residents.reject')
    ->middleware('can:view_residents');
