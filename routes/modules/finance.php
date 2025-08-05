<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentAgreementController;
use App\Http\Controllers\PaymentConceptController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PaymentManagementController;
use App\Http\Controllers\PaymentMethodAccountMappingController;
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

// Invoices Management
Route::resource('invoices', InvoiceController::class)->middleware('can:view_payments');
Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid')->middleware('can:edit_payments');
Route::post('invoices/generate-monthly', [InvoiceController::class, 'generateMonthly'])->name('invoices.generate-monthly')->middleware('can:create_payments');
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
    Route::get('payments/pending-invoices', [PaymentManagementController::class, 'getPendingInvoices'])->name('payments.pending-invoices')->middleware('can:view_payments');
});

// Payment Agreements Management
Route::resource('payment-agreements', PaymentAgreementController::class)->middleware('can:view_payments');
Route::post('payment-agreements/{paymentAgreement}/approve', [PaymentAgreementController::class, 'approve'])->name('payment-agreements.approve')->middleware('can:edit_payments');
Route::post('payment-agreements/{paymentAgreement}/activate', [PaymentAgreementController::class, 'activate'])->name('payment-agreements.activate')->middleware('can:edit_payments');
Route::post('payment-agreements/{paymentAgreement}/cancel', [PaymentAgreementController::class, 'cancel'])->name('payment-agreements.cancel')->middleware('can:edit_payments');
Route::post('payment-agreements/{paymentAgreement}/submit-for-approval', [PaymentAgreementController::class, 'submitForApproval'])->name('payment-agreements.submit-for-approval')->middleware('can:view_payments');
Route::post('payment-agreements/{paymentAgreement}/record-payment', [PaymentAgreementController::class, 'recordPayment'])->name('payment-agreements.record-payment')->middleware('can:edit_payments');

// Providers
Route::get('providers', function () {
    return Inertia::render('Providers/Index');
})->name('providers.index')->middleware(['rate.limit:default', 'can:view_payments']);
