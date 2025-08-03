<?php

use App\Http\Controllers\AccountingReportsController;
use App\Http\Controllers\AccountingTransactionController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\ChartOfAccountsController;
use Illuminate\Support\Facades\Route;

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