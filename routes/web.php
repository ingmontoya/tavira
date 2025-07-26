<?php

use App\Http\Controllers\AccessRequestController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\ConjuntoConfigController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentAgreementController;
use App\Http\Controllers\PaymentConceptController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ResidentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Access Request (Landing Page for registration)
Route::get('solicitar-acceso', [AccessRequestController::class, 'create'])->name('access-request.create');
Route::post('solicitar-acceso', [AccessRequestController::class, 'store'])->name('access-request.store');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('users', function () {
        return Inertia::render('Users/Index');
    })->name('users.index')->middleware('rate.limit:strict');

    // Reports
    Route::get('reports', [ReportsController::class, 'index'])->name('reports.index')->middleware('rate.limit:search');

    // Residents Management
    Route::resource('residents', ResidentController::class);

    // Conjunto Configuration Management (Single conjunto)
    Route::get('conjunto-config', [ConjuntoConfigController::class, 'index'])->name('conjunto-config.index');
    Route::get('conjunto-config/show', [ConjuntoConfigController::class, 'show'])->name('conjunto-config.show');
    Route::get('conjunto-config/edit', [ConjuntoConfigController::class, 'edit'])->name('conjunto-config.edit');
    Route::put('conjunto-config', [ConjuntoConfigController::class, 'update'])->name('conjunto-config.update');
    Route::post('conjunto-config/generate-apartments', [ConjuntoConfigController::class, 'generateApartments'])
        ->name('conjunto-config.generate-apartments');

    // Conjuntos Management
    Route::get('conjuntos', function () {
        return Inertia::render('Conjuntos/Index');
    })->name('conjuntos.index')->middleware('rate.limit:default');

    // Apartments Management
    Route::resource('apartments', ApartmentController::class);
    Route::get('apartments-delinquent', [ApartmentController::class, 'delinquent'])->name('apartments.delinquent');
    Route::get('apartments-delinquent/export-excel', [ApartmentController::class, 'exportDelinquentExcel'])->name('apartments.delinquent.export.excel');
    Route::get('apartments-delinquent/export-pdf', [ApartmentController::class, 'exportDelinquentPdf'])->name('apartments.delinquent.export.pdf');

    // Finance Management
    Route::get('finances', function () {
        return Inertia::render('Finances/Index');
    })->name('finances.index')->middleware('rate.limit:default');

    Route::get('fees', function () {
        return Inertia::render('Fees/Index');
    })->name('fees.index')->middleware('rate.limit:default');

    // Payments Management
    Route::get('payments', [PaymentController::class, 'index'])->name('payments.index')->middleware('rate.limit:default');

    // Invoices Management
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/mark-paid', [InvoiceController::class, 'markAsPaid'])->name('invoices.mark-paid');
    Route::post('invoices/generate-monthly', [InvoiceController::class, 'generateMonthly'])->name('invoices.generate-monthly');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::post('invoices/{invoice}/send-email', [InvoiceController::class, 'sendByEmail'])->name('invoices.send-email');

    // Payment Concepts Management
    Route::resource('payment-concepts', PaymentConceptController::class);
    Route::post('payment-concepts/{paymentConcept}/toggle', [PaymentConceptController::class, 'toggle'])->name('payment-concepts.toggle');

    // Payment Agreements Management
    Route::resource('payment-agreements', PaymentAgreementController::class);
    Route::post('payment-agreements/{paymentAgreement}/approve', [PaymentAgreementController::class, 'approve'])->name('payment-agreements.approve');
    Route::post('payment-agreements/{paymentAgreement}/activate', [PaymentAgreementController::class, 'activate'])->name('payment-agreements.activate');
    Route::post('payment-agreements/{paymentAgreement}/cancel', [PaymentAgreementController::class, 'cancel'])->name('payment-agreements.cancel');
    Route::post('payment-agreements/{paymentAgreement}/submit-for-approval', [PaymentAgreementController::class, 'submitForApproval'])->name('payment-agreements.submit-for-approval');
    Route::post('payment-agreements/{paymentAgreement}/record-payment', [PaymentAgreementController::class, 'recordPayment'])->name('payment-agreements.record-payment');

    Route::get('providers', function () {
        return Inertia::render('Providers/Index');
    })->name('providers.index')->middleware('rate.limit:default');

    // Communication
    Route::get('correspondence', function () {
        return Inertia::render('Correspondence/Index');
    })->name('correspondence.index')->middleware('rate.limit:default');

    Route::get('announcements', function () {
        return Inertia::render('Announcements/Index');
    })->name('announcements.index')->middleware('rate.limit:default');

    Route::get('visits', function () {
        return Inertia::render('Visits/Index');
    })->name('visits.index')->middleware('rate.limit:default');

    // Documents
    Route::get('documents', function () {
        return Inertia::render('Documents/Index');
    })->name('documents.index')->middleware('rate.limit:default');

    Route::get('minutes', function () {
        return Inertia::render('Minutes/Index');
    })->name('minutes.index')->middleware('rate.limit:default');

    // Security
    Route::get('security', function () {
        return Inertia::render('Security/Index');
    })->name('security.main')->middleware('rate.limit:default');

    // Support
    Route::get('support', function () {
        return Inertia::render('Support/Index');
    })->name('support.index')->middleware('rate.limit:default');

    // Documentation
    Route::get('docs', function () {
        return Inertia::render('Docs/Index');
    })->name('docs.index')->middleware('rate.limit:default');

    Route::get('settings', function () {
        return Inertia::render('Settings/Index');
    })->name('settings.index')->middleware('rate.limit:default');

    // Invitations Management
    Route::resource('invitations', InvitationController::class)->except(['edit', 'update']);
    Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
