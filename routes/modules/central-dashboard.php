<?php

use App\Http\Controllers\CentralDashboardController;
use App\Http\Controllers\TenantManagementController;
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
    Route::post('/{tenant}/impersonate', [TenantManagementController::class, 'impersonate'])->name('impersonate');
    Route::post('/{tenant}/login', [TenantManagementController::class, 'loginToTenant'])->name('login');
    Route::post('/{tenant}/suspend', [TenantManagementController::class, 'suspend'])->name('suspend');
    Route::post('/{tenant}/activate', [TenantManagementController::class, 'activate'])->name('activate');
});