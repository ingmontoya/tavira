<?php

use App\Http\Controllers\ExpenseApprovalController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExtraordinaryAssessmentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceEmailController;
use App\Http\Controllers\PaymentAgreementController;
use App\Http\Controllers\PaymentConceptController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentManagementController;
use App\Http\Controllers\PaymentMethodAccountMappingController;
use App\Http\Controllers\ProviderController;
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

// Electronic Invoicing - DIAN Integration
Route::post('api/invoices/{invoice}/electronic-invoice', [InvoiceController::class, 'sendElectronicInvoice'])->name('invoices.send-electronic')->middleware('can:edit_payments');
Route::get('invoices/{invoice}/electronic-pdf', [InvoiceController::class, 'downloadElectronicPdf'])->name('invoices.electronic-pdf')->middleware('can:view_payments');
Route::get('invoices/{invoice}/electronic-xml', [InvoiceController::class, 'downloadElectronicXml'])->name('invoices.electronic-xml')->middleware('can:view_payments');
Route::get('invoices/{invoice}/view-electronic', [InvoiceController::class, 'viewElectronicInvoice'])->name('invoices.view-electronic')->middleware('can:view_payments');

// Payment Concepts Management
Route::resource('payment-concepts', PaymentConceptController::class)->middleware('can:view_payments');
Route::post('payment-concepts/{paymentConcept}/toggle', [PaymentConceptController::class, 'toggle'])->name('payment-concepts.toggle')->middleware('can:edit_payments');

// Payment Method Account Mappings Management
Route::resource('payment-method-account-mappings', PaymentMethodAccountMappingController::class)->middleware('can:view_payments');
Route::post('payment-method-account-mappings/{paymentMethodAccountMapping}/toggle', [PaymentMethodAccountMappingController::class, 'toggle'])->name('payment-method-account-mappings.toggle')->middleware('can:edit_payments');
Route::post('payment-method-account-mappings/create-defaults', [PaymentMethodAccountMappingController::class, 'createDefaults'])->name('payment-method-account-mappings.create-defaults')->middleware('can:edit_payments');

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

// Extraordinary Assessments Management
Route::get('extraordinary-assessments/dashboard', [ExtraordinaryAssessmentController::class, 'dashboard'])->name('extraordinary-assessments.dashboard')->middleware('can:view_payments');
Route::resource('extraordinary-assessments', ExtraordinaryAssessmentController::class)->middleware('can:view_payments');
Route::post('extraordinary-assessments/{extraordinaryAssessment}/activate', [ExtraordinaryAssessmentController::class, 'activate'])->name('extraordinary-assessments.activate')->middleware('can:edit_payments');
Route::post('extraordinary-assessments/{extraordinaryAssessment}/cancel', [ExtraordinaryAssessmentController::class, 'cancel'])->name('extraordinary-assessments.cancel')->middleware('can:edit_payments');

// Email Provider Webhooks (no auth required)
Route::post('webhooks/email/{provider}', [InvoiceEmailController::class, 'webhook'])
    ->name('webhooks.email')
    ->middleware(['rate.limit:strict'])
    ->where('provider', 'sendgrid|ses|mailgun|smtp');

// Providers Management - Resource routes
Route::resource('providers', ProviderController::class)->middleware('can:view_expenses');
Route::post('providers/{provider}/toggle-status', [ProviderController::class, 'toggleStatus'])->name('providers.toggle-status')->middleware('can:edit_expenses');

// Quotation Requests Management - Resource routes
Route::resource('quotation-requests', \App\Http\Controllers\QuotationRequestController::class)->middleware('can:view_expenses');
Route::post('quotation-requests/{quotationRequest}/publish', [\App\Http\Controllers\QuotationRequestController::class, 'publish'])->name('quotation-requests.publish')->middleware('can:edit_expenses');
Route::post('quotation-requests/{quotationRequest}/close', [\App\Http\Controllers\QuotationRequestController::class, 'close'])->name('quotation-requests.close')->middleware('can:edit_expenses');
Route::get('quotation-requests/{quotationRequest}/responses/{response}', [\App\Http\Controllers\QuotationRequestController::class, 'showResponse'])->name('quotation-requests.responses.show')->middleware('can:view_expenses');
Route::get('quotation-requests/{quotationRequest}/responses/{response}/download', [\App\Http\Controllers\QuotationRequestController::class, 'downloadAttachment'])->name('quotation-requests.responses.download')->middleware('can:view_expenses');
Route::post('quotation-requests/{quotationRequest}/responses/{response}/approve', [\App\Http\Controllers\QuotationRequestController::class, 'approveResponse'])->name('quotation-requests.responses.approve')->middleware('can:edit_expenses');
Route::post('quotation-requests/{quotationRequest}/responses/{response}/reject', [\App\Http\Controllers\QuotationRequestController::class, 'rejectResponse'])->name('quotation-requests.responses.reject')->middleware('can:edit_expenses');

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

// Account Statement - Resident view of their financial account
Route::get('account-statement', [\App\Http\Controllers\AccountStatementController::class, 'index'])
    ->name('account-statement.index')
    ->middleware('can:view_account_statement');

Route::get('account-statement/invoice/{invoice}', [\App\Http\Controllers\AccountStatementController::class, 'showInvoice'])
    ->name('account-statement.invoice')
    ->middleware('can:view_account_statement');
