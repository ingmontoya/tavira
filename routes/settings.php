<?php

use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\PaymentSettingsController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\Settings\SecuritySettingsController;
use App\Http\Controllers\Settings\UserPermissionsController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('settings/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');

    // Security Settings Routes
    Route::get('settings/security', [SecuritySettingsController::class, 'index'])->name('security.index');
    Route::post('settings/security', [SecuritySettingsController::class, 'update'])->name('security.update');
    Route::post('settings/security/apply-level', [SecuritySettingsController::class, 'applySecurityLevel'])->name('security.apply-level');
    Route::post('settings/security/reset-defaults', [SecuritySettingsController::class, 'resetToDefaults'])->name('security.reset-defaults');

    // Payment Settings Routes
    Route::get('settings/payments', [PaymentSettingsController::class, 'index'])->name('settings.payments.index');
    Route::post('settings/payments', [PaymentSettingsController::class, 'update'])->name('settings.payments.update');

    // User Permissions Routes
    Route::get('settings/permissions', [UserPermissionsController::class, 'index'])->name('permissions.index')->middleware('can:edit_users');
    Route::patch('settings/permissions/users/{user}/role', [UserPermissionsController::class, 'updateUserRole'])->name('permissions.user.role')->middleware('can:edit_users');
    Route::patch('settings/permissions/users/{user}/permissions', [UserPermissionsController::class, 'updateUserPermissions'])->name('permissions.user.permissions')->middleware('can:edit_users');
    Route::patch('settings/permissions/roles/{role}/permissions', [UserPermissionsController::class, 'updateRolePermissions'])->name('permissions.role.permissions')->middleware('can:edit_users');
});
