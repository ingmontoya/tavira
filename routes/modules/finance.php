<?php

use App\Http\Controllers\ExpenseApprovalController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceEmailController;
use App\Http\Controllers\PaymentAgreementController;
use App\Http\Controllers\PaymentConceptController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentManagementController;
use App\Http\Controllers\PaymentMethodAccountMappingController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Finance Management
Route::get('finances', function () {
    return Inertia::render('Finances/Index');
})->name('finances.index')->middleware(['rate.limit:default', 'can:view_payments']);

Route::get('fees', function () {
    return Inertia::render('Fees/Index');
})->name('fees.index')->middleware(['rate.limit:default', 'can:view_payments']);

// Payments Management
Route::get('payments', [PaymentController::class, 'index'])->name('payments.index')->middleware(['rate.limit:default', 'can:view_payments']);

// Invoice Email Management (Batch Email System) - MUST BE BEFORE resource routes
Route::prefix('invoices/email')->name('invoices.email.')->group(function () {
    Route::get('/', [InvoiceEmailController::class, 'index'])->name('index')->middleware('can:view_payments');
    Route::get('create', [InvoiceEmailController::class, 'create'])->name('create')->middleware('can:edit_payments');
    Route::post('/', [InvoiceEmailController::class, 'store'])->name('store')->middleware('can:edit_payments');
    Route::get('{batch}', [InvoiceEmailController::class, 'show'])->name('show')->middleware('can:view_payments');
    Route::put('{batch}', [InvoiceEmailController::class, 'update'])->name('update')->middleware('can:edit_payments');
    Route::delete('{batch}', [InvoiceEmailController::class, 'destroy'])->name('destroy')->middleware('can:edit_payments');

    // Batch Operations
    Route::post('preview', [InvoiceEmailController::class, 'previewInvoices'])->name('preview')->middleware('can:view_payments');
    Route::post('{batch}/send', [InvoiceEmailController::class, 'send'])->name('send')->middleware('can:edit_payments');
    Route::post('{batch}/cancel', [InvoiceEmailController::class, 'cancel'])->name('cancel')->middleware('can:edit_payments');
    Route::post('{batch}/retry', [InvoiceEmailController::class, 'retry'])->name('retry')->middleware('can:edit_payments');

    // Delivery Management
    Route::get('{batch}/deliveries', [InvoiceEmailController::class, 'deliveries'])->name('deliveries')->middleware('can:view_payments');
});

// Static Invoice Routes - MUST BE BEFORE resource routes
Route::post('invoices/generate-monthly', [InvoiceController::class, 'generateMonthly'])->name('invoices.generate-monthly')->middleware('can:create_payments');

// Invoices Management - Resource routes
Route::resource('invoices', InvoiceController::class)->middleware('can:view_payments');

// Dynamic Invoice Routes - MUST BE AFTER resource routes
Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid')->middleware('can:edit_payments');
Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf')->middleware('can:view_payments');
Route::post('invoices/{invoice}/send-email', [InvoiceController::class, 'sendByEmail'])->name('invoices.send-email')->middleware('can:edit_payments');

// Payment Concepts Management
Route::resource('payment-concepts', PaymentConceptController::class)->middleware('can:view_payments');
Route::post('payment-concepts/{paymentConcept}/toggle', [PaymentConceptController::class, 'toggle'])->name('payment-concepts.toggle')->middleware('can:edit_payments');

// Payment Method Account Mappings Management
Route::resource('payment-method-account-mappings', PaymentMethodAccountMappingController::class)->middleware('can:view_payments');
Route::post('payment-method-account-mappings/{paymentMethodAccountMapping}/toggle', [PaymentMethodAccountMappingController::class, 'toggle'])->name('payment-method-account-mappings.toggle')->middleware('can:edit_payments');

// Payment Management (New System)
Route::prefix('finance')->name('finance.')->group(function () {
    Route::resource('payments', PaymentManagementController::class)->middleware('can:view_payments');
    Route::post('payments/{payment}/apply', [PaymentManagementController::class, 'apply'])->name('payments.apply')->middleware('can:edit_payments');
    Route::post('payments/{payment}/reverse', [PaymentManagementController::class, 'reverse'])->name('payments.reverse')->middleware('can:edit_payments');
    Route::post('payments/{payment}/send-email', [PaymentManagementController::class, 'sendByEmail'])->name('payments.send-email')->middleware('can:edit_payments');
    Route::get('payments/pending-invoices', [PaymentManagementController::class, 'getPendingInvoices'])->name('payments.pending-invoices')->middleware('can:view_payments');
    Route::get('payments/{payment}/edit-invoices', [PaymentManagementController::class, 'getInvoicesForEdit'])->name('payments.edit-invoices')->middleware('can:view_payments');

    // Jelpit Reconciliation
    Route::get('jelpit-reconciliation', [\App\Http\Controllers\JelpitReconciliationController::class, 'index'])->name('jelpit-reconciliation.index')->middleware('can:view_payments');
    Route::post('jelpit-reconciliation/upload', [\App\Http\Controllers\JelpitReconciliationController::class, 'upload'])->name('jelpit-reconciliation.upload')->middleware('can:edit_payments');
    Route::get('jelpit-reconciliation/{import}', [\App\Http\Controllers\JelpitReconciliationController::class, 'show'])->name('jelpit-reconciliation.show')->middleware('can:view_payments');
    Route::post('jelpit-reconciliation/{import}/manual-match', [\App\Http\Controllers\JelpitReconciliationController::class, 'manualMatch'])->name('jelpit-reconciliation.manual-match')->middleware('can:edit_payments');
    Route::post('jelpit-reconciliation/{import}/create-payment', [\App\Http\Controllers\JelpitReconciliationController::class, 'createPayment'])->name('jelpit-reconciliation.create-payment')->middleware('can:edit_payments');
    Route::post('jelpit-reconciliation/{import}/reject', [\App\Http\Controllers\JelpitReconciliationController::class, 'reject'])->name('jelpit-reconciliation.reject')->middleware('can:edit_payments');
    Route::post('jelpit-reconciliation/batch-process', [\App\Http\Controllers\JelpitReconciliationController::class, 'batchProcess'])->name('jelpit-reconciliation.batch-process')->middleware('can:edit_payments');
});

// Payment Agreements Management
Route::resource('payment-agreements', PaymentAgreementController::class)->middleware('can:view_payments');
Route::post('payment-agreements/{paymentAgreement}/approve', [PaymentAgreementController::class, 'approve'])->name('payment-agreements.approve')->middleware('can:edit_payments');
Route::post('payment-agreements/{paymentAgreement}/activate', [PaymentAgreementController::class, 'activate'])->name('payment-agreements.activate')->middleware('can:edit_payments');
Route::post('payment-agreements/{paymentAgreement}/cancel', [PaymentAgreementController::class, 'cancel'])->name('payment-agreements.cancel')->middleware('can:edit_payments');
Route::post('payment-agreements/{paymentAgreement}/submit-for-approval', [PaymentAgreementController::class, 'submitForApproval'])->name('payment-agreements.submit-for-approval')->middleware('can:view_payments');
Route::post('payment-agreements/{paymentAgreement}/record-payment', [PaymentAgreementController::class, 'recordPayment'])->name('payment-agreements.record-payment')->middleware('can:edit_payments');

// Providers
Route::get('providers', [SupplierController::class, 'index'])->name('providers.index')->middleware(['rate.limit:default', 'can:view_payments']);

// Email Provider Webhooks (no auth required)
Route::post('webhooks/email/{provider}', [InvoiceEmailController::class, 'webhook'])
    ->name('webhooks.email')
    ->middleware(['rate.limit:strict'])
    ->where('provider', 'sendgrid|ses|mailgun|smtp');

// Suppliers Management - Resource routes
Route::resource('suppliers', SupplierController::class)->middleware('can:view_expenses');
Route::post('suppliers/{supplier}/toggle-status', [SupplierController::class, 'toggleStatus'])->name('suppliers.toggle-status')->middleware('can:edit_expenses');

// Expense Categories Management
Route::resource('expense-categories', ExpenseCategoryController::class)->middleware('can:manage_expense_categories');

// Expenses Management - Resource routes
Route::resource('expenses', ExpenseController::class)->middleware('can:view_expenses');

// Expense Approval Dashboard
Route::get('expenses/approvals/dashboard', [ExpenseApprovalController::class, 'dashboard'])->name('expenses.approvals.dashboard')->middleware('can:approve_expenses');
Route::post('expenses/bulk-approve', [ExpenseApprovalController::class, 'bulkApprove'])->name('expenses.bulk-approve')->middleware('can:approve_expenses');
Route::post('expenses/bulk-reject', [ExpenseApprovalController::class, 'bulkReject'])->name('expenses.bulk-reject')->middleware('can:approve_expenses');

// Dynamic Expense Routes - MUST BE AFTER resource routes
Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve')->middleware('can:approve_expenses');
Route::post('expenses/{expense}/approve-council', [ExpenseController::class, 'approveByCouncil'])->name('expenses.approve-council')->middleware('can:approve_expenses');
Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject')->middleware('can:approve_expenses');
Route::post('expenses/{expense}/mark-as-paid', [ExpenseController::class, 'markAsPaid'])->name('expenses.mark-as-paid')->middleware('can:edit_expenses');
Route::post('expenses/{expense}/cancel', [ExpenseController::class, 'cancel'])->name('expenses.cancel')->middleware('can:edit_expenses');
Route::post('expenses/{expense}/duplicate', [ExpenseController::class, 'duplicate'])->name('expenses.duplicate')->middleware('can:create_expenses');
