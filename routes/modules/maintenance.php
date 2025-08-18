<?php

use App\Http\Controllers\MaintenanceCategoryController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\MaintenanceStaffController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

// Maintenance Request routes
Route::resource('maintenance-requests', MaintenanceRequestController::class);
Route::get('maintenance-requests-calendar', [MaintenanceRequestController::class, 'calendar'])
    ->name('maintenance-requests.calendar');
Route::patch('maintenance-requests/{maintenanceRequest}/approve', [MaintenanceRequestController::class, 'approve'])
    ->name('maintenance-requests.approve');
Route::patch('maintenance-requests/{maintenanceRequest}/assign', [MaintenanceRequestController::class, 'assign'])
    ->name('maintenance-requests.assign');
Route::patch('maintenance-requests/{maintenanceRequest}/start-work', [MaintenanceRequestController::class, 'startWork'])
    ->name('maintenance-requests.start-work');
Route::patch('maintenance-requests/{maintenanceRequest}/complete', [MaintenanceRequestController::class, 'complete'])
    ->name('maintenance-requests.complete');

// Maintenance Category routes
Route::resource('maintenance-categories', MaintenanceCategoryController::class);
Route::post('maintenance-categories/seed', [MaintenanceCategoryController::class, 'seedCategories'])
    ->name('maintenance-categories.seed');

// Maintenance Staff routes
Route::resource('maintenance-staff', MaintenanceStaffController::class);

// Work Order routes
Route::resource('work-orders', WorkOrderController::class);
