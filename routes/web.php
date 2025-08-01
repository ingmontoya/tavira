<?php

use App\Http\Controllers\AccessRequestController;
use App\Http\Controllers\AccountingReportsController;
use App\Http\Controllers\AccountingTransactionController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ChartOfAccountsController;
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

    // New modules - Under construction
    Route::get('account-statement', function () {
        return Inertia::render('AccountStatement/Index');
    })->name('account-statement.index')->middleware('can:view_account_statement');

    Route::get('visitor-invitations', function () {
        return Inertia::render('VisitorInvitations/Index');
    })->name('visitor-invitations.index')->middleware('can:invite_visitors');

    Route::get('notifications', function () {
        return Inertia::render('Notifications/Index');
    })->name('notifications.index')->middleware('can:receive_notifications');

    Route::get('pqrs', function () {
        return Inertia::render('PQRS/Index');
    })->name('pqrs.index')->middleware('can:send_pqrs');

    Route::get('messages', function () {
        return Inertia::render('Messages/Index');
    })->name('messages.index')->middleware('can:send_messages_to_admin');

    Route::get('provider-proposals', function () {
        return Inertia::render('ProviderProposals/Index');
    })->name('provider-proposals.index')->middleware('can:review_provider_proposals');

    Route::get('users', function () {
        return Inertia::render('Users/Index');
    })->name('users.index')->middleware('rate.limit:strict');

    // Reports
    Route::get('reports', [ReportsController::class, 'index'])->name('reports.index')->middleware(['rate.limit:search', 'can:view_reports']);

    // Residents Management
    Route::resource('residents', ResidentController::class)->middleware('can:view_residents');

    // Conjunto Configuration Management (Single conjunto)
    Route::get('conjunto-config', [ConjuntoConfigController::class, 'index'])->name('conjunto-config.index')->middleware('can:view_conjunto_config');
    Route::get('conjunto-config/create', [ConjuntoConfigController::class, 'create'])->name('conjunto-config.create')->middleware('can:edit_conjunto_config');
    Route::post('conjunto-config', [ConjuntoConfigController::class, 'store'])->name('conjunto-config.store')->middleware('can:edit_conjunto_config');
    Route::get('conjunto-config/show', [ConjuntoConfigController::class, 'show'])->name('conjunto-config.show')->middleware('can:view_conjunto_config');
    Route::get('conjunto-config/edit', [ConjuntoConfigController::class, 'edit'])->name('conjunto-config.edit')->middleware('can:edit_conjunto_config');
    Route::put('conjunto-config', [ConjuntoConfigController::class, 'update'])->name('conjunto-config.update')->middleware('can:edit_conjunto_config');
    Route::post('conjunto-config/generate-apartments', [ConjuntoConfigController::class, 'generateApartments'])
        ->name('conjunto-config.generate-apartments')->middleware('can:edit_conjunto_config');

    // Conjuntos Management
    Route::get('conjuntos', function () {
        return Inertia::render('Conjuntos/Index');
    })->name('conjuntos.index')->middleware(['rate.limit:default', 'can:view_conjunto_config']);

    // Apartments Management
    Route::resource('apartments', ApartmentController::class)->middleware('can:view_apartments');
    Route::get('apartments-delinquent', [ApartmentController::class, 'delinquent'])->name('apartments.delinquent')->middleware('can:view_apartments');
    Route::get('apartments-delinquent/export-excel', [ApartmentController::class, 'exportDelinquentExcel'])->name('apartments.delinquent.export.excel')->middleware('can:view_apartments');
    Route::get('apartments-delinquent/export-pdf', [ApartmentController::class, 'exportDelinquentPdf'])->name('apartments.delinquent.export.pdf')->middleware('can:view_apartments');

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

    // Payment Agreements Management
    Route::resource('payment-agreements', PaymentAgreementController::class)->middleware('can:view_payments');
    Route::post('payment-agreements/{paymentAgreement}/approve', [PaymentAgreementController::class, 'approve'])->name('payment-agreements.approve')->middleware('can:edit_payments');
    Route::post('payment-agreements/{paymentAgreement}/activate', [PaymentAgreementController::class, 'activate'])->name('payment-agreements.activate')->middleware('can:edit_payments');
    Route::post('payment-agreements/{paymentAgreement}/cancel', [PaymentAgreementController::class, 'cancel'])->name('payment-agreements.cancel')->middleware('can:edit_payments');
    Route::post('payment-agreements/{paymentAgreement}/submit-for-approval', [PaymentAgreementController::class, 'submitForApproval'])->name('payment-agreements.submit-for-approval')->middleware('can:view_payments');
    Route::post('payment-agreements/{paymentAgreement}/record-payment', [PaymentAgreementController::class, 'recordPayment'])->name('payment-agreements.record-payment')->middleware('can:edit_payments');

    // Accounting System
    Route::prefix('accounting')->name('accounting.')->middleware('can:view_accounting')->group(function () {
        // Chart of Accounts
        Route::resource('chart-of-accounts', ChartOfAccountsController::class);
        Route::get('chart-of-accounts/{chartOfAccount}/balance', [ChartOfAccountsController::class, 'getBalance'])->name('chart-of-accounts.balance');
        Route::get('accounts/by-type', [ChartOfAccountsController::class, 'getByType'])->name('accounts.by-type');
        Route::get('accounts/hierarchical', [ChartOfAccountsController::class, 'getHierarchical'])->name('accounts.hierarchical');

        // Accounting Transactions
        Route::resource('transactions', AccountingTransactionController::class, ['as' => 'accounting-transactions']);
        Route::post('transactions/{accountingTransaction}/post', [AccountingTransactionController::class, 'post'])->name('transactions.post');
        Route::post('transactions/{accountingTransaction}/cancel', [AccountingTransactionController::class, 'cancel'])->name('transactions.cancel');
        Route::post('transactions/{accountingTransaction}/entries', [AccountingTransactionController::class, 'addEntry'])->name('transactions.add-entry');
        Route::delete('transactions/{accountingTransaction}/entries/{entry}', [AccountingTransactionController::class, 'removeEntry'])->name('transactions.remove-entry');
        Route::post('transactions/validate-double-entry', [AccountingTransactionController::class, 'validateDoubleEntry'])->name('transactions.validate-double-entry');

        // Budgets
        Route::resource('budgets', BudgetController::class);
        Route::post('budgets/{budget}/approve', [BudgetController::class, 'approve'])->name('budgets.approve');
        Route::post('budgets/{budget}/activate', [BudgetController::class, 'activate'])->name('budgets.activate');
        Route::post('budgets/{budget}/close', [BudgetController::class, 'close'])->name('budgets.close');
        Route::get('budgets/{budget}/execution', [BudgetController::class, 'execution'])->name('budgets.execution');
        Route::post('budgets/copy-from-previous', [BudgetController::class, 'copyFromPrevious'])->name('budgets.copy-from-previous');

        // Accounting Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [AccountingReportsController::class, 'index'])->name('index');
            Route::get('balance-sheet', [AccountingReportsController::class, 'balanceSheet'])->name('balance-sheet');
            Route::get('income-statement', [AccountingReportsController::class, 'incomeStatement'])->name('income-statement');
            Route::get('trial-balance', [AccountingReportsController::class, 'trialBalance'])->name('trial-balance');
            Route::get('general-ledger', [AccountingReportsController::class, 'generalLedger'])->name('general-ledger');
            Route::get('budget-execution', [AccountingReportsController::class, 'budgetExecution'])->name('budget-execution');
            Route::get('cash-flow', [AccountingReportsController::class, 'cashFlow'])->name('cash-flow');
        });
    });

    Route::get('providers', function () {
        return Inertia::render('Providers/Index');
    })->name('providers.index')->middleware(['rate.limit:default', 'can:view_payments']);

    // Communication
    Route::get('correspondence', function () {
        return Inertia::render('Correspondence/Index');
    })->name('correspondence.index')->middleware(['rate.limit:default', 'can:view_announcements']);

    Route::get('announcements', function () {
        return Inertia::render('Announcements/Index');
    })->name('announcements.index')->middleware(['rate.limit:default', 'can:view_announcements']);

    Route::get('visits', function () {
        return Inertia::render('Visits/Index');
    })->name('visits.index')->middleware(['rate.limit:default', 'can:manage_visitors']);

    // Documents
    Route::get('documents', function () {
        return Inertia::render('Documents/Index');
    })->name('documents.index')->middleware(['rate.limit:default', 'can:view_announcements']);

    Route::get('minutes', function () {
        return Inertia::render('Minutes/Index');
    })->name('minutes.index')->middleware(['rate.limit:default', 'can:view_announcements']);

    // Security
    Route::get('security', function () {
        return Inertia::render('Security/Index');
    })->name('security.main')->middleware(['rate.limit:default', 'can:view_access_logs']);

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
    })->name('settings.index')->middleware(['rate.limit:default', 'can:edit_conjunto_config']);

    // Invitations Management
    Route::resource('invitations', InvitationController::class)->except(['edit', 'update'])->middleware('can:manage_invitations');
    Route::post('invitations/mass', [InvitationController::class, 'storeMass'])->name('invitations.mass.store')->middleware('can:manage_invitations');
    Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend')->middleware('can:manage_invitations');
    Route::get('invitations/{invitation}/url', [InvitationController::class, 'getRegistrationUrl'])->name('invitations.url')->middleware('can:manage_invitations');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
