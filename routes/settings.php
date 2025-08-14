<?php

use App\Http\Controllers\Settings\AccountingSettingsController;
use App\Http\Controllers\Settings\EmailSettingsController;
use App\Http\Controllers\Settings\ExpenseSettingsController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\PaymentConceptMappingController;
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

    // Email Settings Routes
    Route::get('settings/email', [EmailSettingsController::class, 'index'])->name('settings.email.index')->middleware('can:edit_conjunto_config');
    Route::post('settings/email', [EmailSettingsController::class, 'update'])->name('settings.email.update')->middleware('can:edit_conjunto_config');
    Route::post('settings/email/test-connection', [EmailSettingsController::class, 'testConnection'])->name('settings.email.test-connection')->middleware('can:edit_conjunto_config');
    Route::post('settings/email/test-email', [EmailSettingsController::class, 'testEmail'])->name('settings.email.test-email')->middleware('can:edit_conjunto_config');
    Route::post('settings/email/apply-preset', [EmailSettingsController::class, 'applyPreset'])->name('settings.email.apply-preset')->middleware('can:edit_conjunto_config');
    Route::post('settings/email/reset-defaults', [EmailSettingsController::class, 'resetToDefaults'])->name('settings.email.reset-defaults')->middleware('can:edit_conjunto_config');

    // Payment Settings Routes
    Route::get('settings/payments', [PaymentSettingsController::class, 'index'])->name('settings.payments.index');
    Route::post('settings/payments', [PaymentSettingsController::class, 'update'])->name('settings.payments.update');

    // Expense Settings Routes
    Route::get('settings/expenses', [ExpenseSettingsController::class, 'index'])->name('settings.expenses.index')->middleware('can:manage_expense_settings');
    Route::post('settings/expenses', [ExpenseSettingsController::class, 'update'])->name('settings.expenses.update')->middleware('can:manage_expense_settings');

    // User Permissions Routes
    Route::get('settings/permissions', [UserPermissionsController::class, 'index'])->name('permissions.index')->middleware('can:edit_users');
    Route::patch('settings/permissions/users/{user}/role', [UserPermissionsController::class, 'updateUserRole'])->name('permissions.user.role')->middleware('can:edit_users');
    Route::patch('settings/permissions/users/{user}/permissions', [UserPermissionsController::class, 'updateUserPermissions'])->name('permissions.user.permissions')->middleware('can:edit_users');
    Route::patch('settings/permissions/roles/{role}/permissions', [UserPermissionsController::class, 'updateRolePermissions'])->name('permissions.role.permissions')->middleware('can:edit_users');

    // Payment Concept Mapping Routes
    Route::get('settings/payment-concept-mapping', [PaymentConceptMappingController::class, 'index'])->name('settings.payment-concept-mapping.index')->middleware('can:manage_accounting');
    Route::post('settings/payment-concept-mapping', [PaymentConceptMappingController::class, 'store'])->name('settings.payment-concept-mapping.store')->middleware('can:manage_accounting');
    Route::put('settings/payment-concept-mapping/{mapping}', [PaymentConceptMappingController::class, 'update'])->name('settings.payment-concept-mapping.update')->middleware('can:manage_accounting');
    Route::delete('settings/payment-concept-mapping/{mapping}', [PaymentConceptMappingController::class, 'destroy'])->name('settings.payment-concept-mapping.destroy')->middleware('can:manage_accounting');
    Route::post('settings/payment-concept-mapping/create-defaults', [PaymentConceptMappingController::class, 'createDefaultMappings'])->name('settings.payment-concept-mapping.create-defaults')->middleware('can:manage_accounting');
    Route::post('settings/payment-concept-mapping/{mapping}/toggle-active', [PaymentConceptMappingController::class, 'toggleActive'])->name('settings.payment-concept-mapping.toggle-active')->middleware('can:manage_accounting');

    // Accounting Settings Routes
    Route::get('settings/accounting', [AccountingSettingsController::class, 'index'])->name('settings.accounting.index')->middleware('can:manage_accounting');
    Route::post('settings/accounting/initialize-accounts', [AccountingSettingsController::class, 'initializeAccounts'])->name('settings.accounting.initialize-accounts')->middleware('can:manage_accounting');
});
