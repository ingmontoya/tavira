<?php

use App\Http\Controllers\MaintenanceCategoryController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\MaintenanceRequestDocumentController;
use App\Http\Controllers\MaintenanceStaffController;
use App\Http\Controllers\WorkOrderController;
use Illuminate\Support\Facades\Route;

// Maintenance Request routes (require maintenance_requests feature)
Route::middleware('requires.feature:maintenance_requests')->group(function () {
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
    Route::patch('maintenance-requests/{maintenanceRequest}/next-state', [MaintenanceRequestController::class, 'nextState'])
        ->name('maintenance-requests.next-state');
    Route::post('maintenance-requests/{maintenanceRequest}/pause-recurrence', [MaintenanceRequestController::class, 'pauseRecurrence'])
        ->name('maintenance-requests.pause-recurrence');
    Route::post('maintenance-requests/{maintenanceRequest}/resume-recurrence', [MaintenanceRequestController::class, 'resumeRecurrence'])
        ->name('maintenance-requests.resume-recurrence');

    // Maintenance Request Documents routes
    Route::get('maintenance-requests/{maintenanceRequest}/documents', [MaintenanceRequestDocumentController::class, 'index'])
        ->name('maintenance-requests.documents.index');
    Route::post('maintenance-requests/{maintenanceRequest}/documents', [MaintenanceRequestDocumentController::class, 'store'])
        ->name('maintenance-requests.documents.store');
    Route::get('maintenance-requests/{maintenanceRequest}/documents/{document}', [MaintenanceRequestDocumentController::class, 'show'])
        ->name('maintenance-requests.documents.show');
    Route::patch('maintenance-requests/{maintenanceRequest}/documents/{document}', [MaintenanceRequestDocumentController::class, 'update'])
        ->name('maintenance-requests.documents.update');
    Route::get('maintenance-requests/{maintenanceRequest}/documents/{document}/download', [MaintenanceRequestDocumentController::class, 'download'])
        ->name('maintenance-requests.documents.download');
    Route::delete('maintenance-requests/{maintenanceRequest}/documents/{document}', [MaintenanceRequestDocumentController::class, 'destroy'])
        ->name('maintenance-requests.documents.destroy');

    // Maintenance Category routes
    Route::resource('maintenance-categories', MaintenanceCategoryController::class);
    Route::post('maintenance-categories/seed', [MaintenanceCategoryController::class, 'seedCategories'])
        ->name('maintenance-categories.seed');

    // Maintenance Staff routes
    Route::resource('maintenance-staff', MaintenanceStaffController::class);

    // Work Order routes
    Route::resource('work-orders', WorkOrderController::class);
});
