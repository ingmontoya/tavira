<?php

use App\Http\Controllers\CentralDashboardController;
use App\Http\Controllers\TenantManagementController;
use App\Http\Controllers\TenantFeatureController;
use Illuminate\Support\Facades\Route;

// Central Dashboard for tenant management
Route::get('dashboard', [CentralDashboardController::class, 'index'])->name('dashboard');

// Tenant Management Routes (superadmin only)
Route::prefix('tenants')->name('tenant-management.')->group(function () {
    Route::get('/', [TenantManagementController::class, 'index'])->name('index');
    Route::get('/create', [TenantManagementController::class, 'create'])->name('create');
    Route::post('/', [TenantManagementController::class, 'store'])->name('store');
    Route::get('/{tenant}', [TenantManagementController::class, 'show'])->name('show');
    Route::get('/{tenant}/edit', [TenantManagementController::class, 'edit'])->name('edit');
    Route::put('/{tenant}', [TenantManagementController::class, 'update'])->name('update');
    Route::delete('/{tenant}', [TenantManagementController::class, 'destroy'])->name('destroy');
    
    // Tenant actions
    Route::get('/{tenant}/users', [TenantManagementController::class, 'getUsers'])->name('users');
    Route::post('/{tenant}/impersonate', [TenantManagementController::class, 'impersonate'])->name('impersonate');
    Route::post('/{tenant}/suspend', [TenantManagementController::class, 'suspend'])->name('suspend');
    Route::post('/{tenant}/activate', [TenantManagementController::class, 'activate'])->name('activate');
});

// Feature Management Routes (superadmin only) - Uses dedicated controller without tenancy conflicts
Route::prefix('tenant-features')->name('tenant-features.')->group(function () {
    Route::get('/', [\App\Http\Controllers\CentralTenantFeatureController::class, 'index'])->name('index');
    Route::put('/{tenant}/{feature}', [\App\Http\Controllers\CentralTenantFeatureController::class, 'updateFeature'])->name('update-feature');
    Route::put('/{tenant}/bulk', [\App\Http\Controllers\CentralTenantFeatureController::class, 'bulkUpdateFeatures'])->name('bulk-update');
    Route::post('/{tenant}/template', [\App\Http\Controllers\CentralTenantFeatureController::class, 'applyTemplate'])->name('apply-template');
});