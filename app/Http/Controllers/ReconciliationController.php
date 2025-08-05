<?php

namespace App\Http\Controllers;

use App\Models\AccountingTransactionEntry;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ReconciliationController extends Controller
{
    public function index()
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $bankAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->where('code', 'LIKE', '111%') // Bank accounts
            ->active()
            ->orderBy('code')
            ->get();

        return Inertia::render('Accounting/Reconciliation/Index', [
            'bank_accounts' => $bankAccounts,
        ]);
    }

    public function show(Request $request, ChartOfAccounts $account)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        // Get opening balance
        $openingBalance = $account->getBalance(null, $startDate);

        // Get transactions for the period
        $transactions = AccountingTransactionEntry::with(['transaction'])
            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'posted')
                    ->whereBetween('transaction_date', [$startDate, $endDate]);
            })
            ->where('account_id', $account->id)
            ->orderBy('created_at')
            ->get();

        $runningBalance = $openingBalance;
        $transactionData = [];

        foreach ($transactions as $entry) {
            $amount = $entry->debit_amount - $entry->credit_amount;
            $runningBalance += $amount;

            $transactionData[] = [
                'id' => $entry->id,
                'date' => $entry->transaction->transaction_date,
                'transaction_number' => $entry->transaction->transaction_number,
                'description' => $entry->description,
                'debit' => $entry->debit_amount,
                'credit' => $entry->credit_amount,
                'amount' => $amount,
                'balance' => $runningBalance,
                'is_reconciled' => false, // In a real system, you'd track this
                'reference' => $entry->transaction->reference_type,
            ];
        }

        $closingBalance = $runningBalance;

        return Inertia::render('Accounting/Reconciliation/Show', [
            'account' => $account,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'transactions' => $transactionData,
            'summary' => [
                'total_debits' => $transactions->sum('debit_amount'),
                'total_credits' => $transactions->sum('credit_amount'),
                'net_change' => $closingBalance - $openingBalance,
                'transaction_count' => $transactions->count(),
            ],
        ]);
    }

    public function create(ChartOfAccounts $account)
    {
        return Inertia::render('Accounting/Reconciliation/Create', [
            'account' => $account,
        ]);
    }

    public function store(Request $request, ChartOfAccounts $account)
    {
        $validated = $request->validate([
            'statement_date' => 'required|date',
            'statement_balance' => 'required|numeric',
            'reconciled_transactions' => 'required|array',
            'reconciled_transactions.*' => 'integer|exists:accounting_transaction_entries,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Calculate book balance for reconciled transactions
        $reconciledEntries = AccountingTransactionEntry::whereIn('id', $validated['reconciled_transactions'])
            ->where('account_id', $account->id)
            ->get();

        $bookBalance = $reconciledEntries->sum('debit_amount') - $reconciledEntries->sum('credit_amount');
        $difference = $validated['statement_balance'] - $bookBalance;

        // In a real implementation, you would:
        // 1. Create a BankReconciliation model to store the reconciliation
        // 2. Mark entries as reconciled
        // 3. Handle reconciling items and adjustments

        $reconciliationData = [
            'account_id' => $account->id,
            'statement_date' => $validated['statement_date'],
            'statement_balance' => $validated['statement_balance'],
            'book_balance' => $bookBalance,
            'difference' => $difference,
            'reconciled_entries_count' => count($validated['reconciled_transactions']),
            'notes' => $validated['notes'],
            'is_balanced' => abs($difference) < 0.01,
        ];

        return redirect()
            ->route('reconciliation.show', $account)
            ->with('success', 'Conciliación bancaria procesada exitosamente.')
            ->with('reconciliation_result', $reconciliationData);
    }

    public function reconciliationReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $conjunto = ConjuntoConfig::where('is_active', true)->first();
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $bankAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->where('code', 'LIKE', '111%')
            ->active()
            ->get();

        $reconciliationData = [];
        foreach ($bankAccounts as $account) {
            $entries = AccountingTransactionEntry::whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'posted')
                    ->whereBetween('transaction_date', [$startDate, $endDate]);
            })
                ->where('account_id', $account->id)
                ->get();

            if ($entries->isNotEmpty()) {
                $reconciliationData[] = [
                    'account' => $account,
                    'opening_balance' => $account->getBalance(null, $startDate),
                    'closing_balance' => $account->getBalance(null, $endDate),
                    'total_debits' => $entries->sum('debit_amount'),
                    'total_credits' => $entries->sum('credit_amount'),
                    'transaction_count' => $entries->count(),
                    'unreconciled_count' => $entries->count(), // In real system, filter by reconciled status
                ];
            }
        }

        return Inertia::render('Accounting/Reconciliation/Report', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'reconciliation_data' => $reconciliationData,
            'summary' => [
                'total_accounts' => count($reconciliationData),
                'total_transactions' => collect($reconciliationData)->sum('transaction_count'),
                'total_unreconciled' => collect($reconciliationData)->sum('unreconciled_count'),
            ],
        ]);
    }

    public function adjustmentEntry(Request $request, ChartOfAccounts $account)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric',
            'type' => 'required|in:debit,credit',
            'description' => 'required|string|max:255',
            'transaction_date' => 'required|date',
        ]);

        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        // Create adjustment transaction
        DB::transaction(function () use ($validated, $account, $conjunto) {
            $transaction = \App\Models\AccountingTransaction::create([
                'conjunto_config_id' => $conjunto->id,
                'transaction_date' => $validated['transaction_date'],
                'description' => 'Ajuste de conciliación bancaria - '.$validated['description'],
                'reference_type' => 'bank_reconciliation',
                'status' => 'borrador',
                'created_by' => auth()->id(),
            ]);

            // Add bank account entry
            $transaction->addEntry([
                'account_id' => $account->id,
                'description' => $validated['description'],
                'debit_amount' => $validated['type'] === 'debit' ? $validated['amount'] : 0,
                'credit_amount' => $validated['type'] === 'credit' ? $validated['amount'] : 0,
            ]);

            // Add offsetting entry (suspense account or specific adjustment account)
            $adjustmentAccount = ChartOfAccounts::forConjunto($conjunto->id)
                ->where('code', '519999') // Adjustment account
                ->first();

            if ($adjustmentAccount) {
                $transaction->addEntry([
                    'account_id' => $adjustmentAccount->id,
                    'description' => 'Contrapartida - '.$validated['description'],
                    'debit_amount' => $validated['type'] === 'credit' ? $validated['amount'] : 0,
                    'credit_amount' => $validated['type'] === 'debit' ? $validated['amount'] : 0,
                ]);
            }

            $transaction->post();
        });

        return response()->json([
            'message' => 'Asiento de ajuste creado exitosamente.',
        ]);
    }
}
