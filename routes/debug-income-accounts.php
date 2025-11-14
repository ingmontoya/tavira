<?php

use App\Models\AccountingTransactionEntry;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Support\Facades\Route;

Route::get('/debug/income-accounts', function () {
    if (! config('app.debug')) {
        abort(404);
    }

    $conjunto = ConjuntoConfig::first();

    // Get all income accounts
    $incomeAccounts = ChartOfAccounts::forConjunto($conjunto->id)
        ->where('account_type', 'income')
        ->where('is_active', true)
        ->orderBy('code')
        ->get();

    $result = [];
    foreach ($incomeAccounts as $account) {
        $entriesCount = AccountingTransactionEntry::where('account_id', $account->id)
            ->whereHas('transaction', function ($query) {
                $query->where('status', 'contabilizado');
            })
            ->count();

        $balance = $account->getBalance();

        $result[] = [
            'code' => $account->code,
            'name' => $account->name,
            'accepts_posting' => $account->accepts_posting,
            'entries_count' => $entriesCount,
            'balance' => number_format($balance, 2),
            'will_show_in_report' => $balance != 0,
        ];
    }

    return response()->json([
        'total_income_accounts' => count($result),
        'accounts_with_417005' => collect($result)->where('code', '417005')->count() > 0,
        'account_417005' => collect($result)->firstWhere('code', '417005'),
        'all_income_accounts' => $result,
    ]);
})->middleware('web');
