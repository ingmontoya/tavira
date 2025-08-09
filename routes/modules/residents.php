<?php

use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ResidentDashboardController;
use Illuminate\Support\Facades\Route;

// Resident Dashboard - accessible to residents
Route::get('resident/dashboard', [ResidentDashboardController::class, 'index'])->name('resident.dashboard');

// Residents Management - admin only
Route::resource('residents', ResidentController::class)->middleware('can:view_residents');
