<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetExecution;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountingReportsController extends Controller
{
    public function index()
    {
        return Inertia::render('Accounting/Reports/Index', [
            'availableReports' => [
                'balance-sheet' => [
                    'name' => 'Balance General',
                    'description' => 'Estado de situación financiera',
                    'icon' => 'scale',
                ],
                'income-statement' => [
                    'name' => 'Estado de Resultados',
                    'description' => 'Ingresos y gastos del período',
                    'icon' => 'trending',
                ],
                'trial-balance' => [
                    'name' => 'Balance de Prueba',
                    'description' => 'Saldos de todas las cuentas',
                    'icon' => 'calculator',
                ],
                'general-ledger' => [
                    'name' => 'Libro Mayor',
                    'description' => 'Movimientos detallados por cuenta',
                    'icon' => 'book',
                ],
                'budget-execution' => [
                    'name' => 'Ejecución Presupuestal',
                    'description' => 'Comparativo presupuesto vs real',
                    'icon' => 'chart',
                ],
                'cash-flow' => [
                    'name' => 'Flujo de Caja',
                    'description' => 'Entradas y salidas de efectivo',
                    'icon' => 'dollar',
                ],
            ],
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'as_of_date' => 'required|date',
        ]);

        $asOfDate = $validated['as_of_date'];

        // Assets
        $assets = $this->getAccountBalances($conjunto->id, 'asset', null, $asOfDate);
        $totalAssets = $assets->sum('balance');

        // Liabilities
        $liabilities = $this->getAccountBalances($conjunto->id, 'liability', null, $asOfDate);
        $totalLiabilities = $liabilities->sum('balance');

        // Equity
        $equity = $this->getAccountBalances($conjunto->id, 'equity', null, $asOfDate);
        $totalEquity = $equity->sum('balance');

        return Inertia::render('Accounting/Reports/BalanceSheet', [
            'report' => [
                'as_of_date' => $asOfDate,
                'assets' => $assets,
                'liabilities' => $liabilities,
                'equity' => $equity,
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'total_equity' => $totalEquity,
                'total_liabilities_equity' => $totalLiabilities + $totalEquity,
                'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
            ],
            'filters' => [
                'as_of_date' => $asOfDate,
            ],
        ]);
    }

    public function incomeStatement(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        // Income
        $income = $this->getAccountBalances($conjunto->id, 'income', $startDate, $endDate);
        $totalIncome = $income->sum('balance');

        // Expenses
        $expenses = $this->getAccountBalances($conjunto->id, 'expense', $startDate, $endDate);
        $totalExpenses = $expenses->sum('balance');

        $netIncome = $totalIncome - $totalExpenses;

        return Inertia::render('Accounting/Reports/IncomeStatement', [
            'report' => [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'description' => 'Del '.date('d/m/Y', strtotime($startDate)).' al '.date('d/m/Y', strtotime($endDate)),
                ],
                'income' => $income,
                'expenses' => $expenses,
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_income' => $netIncome,
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    public function trialBalance(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'as_of_date' => 'required|date',
        ]);

        $asOfDate = $validated['as_of_date'];

        $accounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->active()
            ->with(['transactionEntries' => function ($query) use ($asOfDate) {
                $query->whereHas('transaction', function ($q) use ($asOfDate) {
                    $q->where('status', 'posted')
                        ->where('transaction_date', '<=', $asOfDate);
                });
            }])
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($asOfDate) {
                $balance = $account->getBalance(null, $asOfDate);

                return [
                    'code' => $account->code,
                    'name' => $account->name,
                    'account_type' => $account->account_type,
                    'nature' => $account->nature,
                    'debit_balance' => $account->nature === 'debit' && $balance > 0 ? $balance : 0,
                    'credit_balance' => $account->nature === 'credit' && $balance > 0 ? $balance : 0,
                    'balance' => $balance,
                ];
            })
            ->filter(fn ($account) => $account['balance'] != 0);

        $totalDebits = $accounts->sum('debit_balance');
        $totalCredits = $accounts->sum('credit_balance');

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'report' => [
                'as_of_date' => $asOfDate,
                'accounts' => $accounts,
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
            ],
            'filters' => [
                'as_of_date' => $asOfDate,
            ],
        ]);
    }

    public function generalLedger(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'account_id' => 'required|exists:chart_of_accounts,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $account = ChartOfAccounts::forConjunto($conjunto->id)->findOrFail($validated['account_id']);
        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        // Get opening balance
        $openingBalance = $account->getBalance(null, date('Y-m-d', strtotime($startDate.' -1 day')));

        // Get transactions for the period
        $entries = $account->transactionEntries()
            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'posted')
                    ->whereBetween('transaction_date', [$startDate, $endDate]);
            })
            ->with(['transaction'])
            ->orderBy('created_at')
            ->get();

        $runningBalance = $openingBalance;
        $ledgerEntries = $entries->map(function ($entry) use (&$runningBalance, $account) {
            $movement = $account->nature === 'debit'
                ? $entry->debit_amount - $entry->credit_amount
                : $entry->credit_amount - $entry->debit_amount;

            $runningBalance += $movement;

            return [
                'date' => $entry->transaction->transaction_date->format('Y-m-d'),
                'transaction_number' => $entry->transaction->transaction_number,
                'description' => $entry->description,
                'debit_amount' => $entry->debit_amount,
                'credit_amount' => $entry->credit_amount,
                'balance' => $runningBalance,
            ];
        });

        return Inertia::render('Accounting/Reports/GeneralLedger', [
            'report' => [
                'account' => $account,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'opening_balance' => $openingBalance,
                'closing_balance' => $runningBalance,
                'entries' => $ledgerEntries,
                'total_debits' => $entries->sum('debit_amount'),
                'total_credits' => $entries->sum('credit_amount'),
            ],
            'filters' => [
                'account_id' => $validated['account_id'],
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            'accounts' => ChartOfAccounts::forConjunto($conjunto->id)
                ->active()
                ->orderBy('code')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function budgetExecution(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'budget_id' => 'nullable|exists:budgets,id',
            'period_month' => 'nullable|integer|min:1|max:12',
            'period_year' => 'nullable|integer|min:2020|max:2050',
        ]);

        $budget = null;
        if ($validated['budget_id']) {
            $budget = Budget::forConjunto($conjunto->id)->findOrFail($validated['budget_id']);
        } else {
            $budget = Budget::forConjunto($conjunto->id)->active()->first();
        }

        if (! $budget) {
            return back()->withErrors(['budget' => 'No hay presupuesto activo disponible.']);
        }

        $month = $validated['period_month'] ?? now()->month;
        $year = $validated['period_year'] ?? $budget->fiscal_year;

        $executionSummary = BudgetExecution::getSummaryByPeriod($conjunto->id, $month, $year);
        $ytdSummary = BudgetExecution::getYearToDateSummary($conjunto->id, $year);

        return Inertia::render('Accounting/Reports/BudgetExecution', [
            'report' => [
                'budget' => $budget,
                'period' => [
                    'month' => $month,
                    'year' => $year,
                    'name' => $this->getMonthName($month).' '.$year,
                ],
                'monthly_execution' => $executionSummary,
                'ytd_execution' => $ytdSummary,
            ],
            'filters' => [
                'budget_id' => $budget->id,
                'period_month' => $month,
                'period_year' => $year,
            ],
            'budgets' => Budget::forConjunto($conjunto->id)
                ->orderBy('fiscal_year', 'desc')
                ->get(['id', 'name', 'fiscal_year', 'status']),
            'availableMonths' => $this->getAvailableMonths(),
        ]);
    }

    public function cashFlow(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'];
        $endDate = $validated['end_date'];

        // Get cash and bank accounts (typically accounts starting with 111)
        $cashAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->where('code', 'like', '111%')
            ->active()
            ->get();

        $cashFlow = $cashAccounts->map(function ($account) use ($startDate, $endDate) {
            $openingBalance = $account->getBalance(null, date('Y-m-d', strtotime($startDate.' -1 day')));
            $closingBalance = $account->getBalance(null, $endDate);
            $netChange = $closingBalance - $openingBalance;

            $entries = $account->transactionEntries()
                ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                    $query->where('status', 'posted')
                        ->whereBetween('transaction_date', [$startDate, $endDate]);
                })
                ->with('transaction')
                ->get();

            $inflows = $entries->where('debit_amount', '>', 0)->sum('debit_amount');
            $outflows = $entries->where('credit_amount', '>', 0)->sum('credit_amount');

            return [
                'account' => $account,
                'opening_balance' => $openingBalance,
                'closing_balance' => $closingBalance,
                'net_change' => $netChange,
                'inflows' => $inflows,
                'outflows' => $outflows,
                'entries' => $entries,
            ];
        });

        return Inertia::render('Accounting/Reports/CashFlow', [
            'report' => [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'cash_accounts' => $cashFlow,
                'total_opening' => $cashFlow->sum('opening_balance'),
                'total_closing' => $cashFlow->sum('closing_balance'),
                'total_inflows' => $cashFlow->sum('inflows'),
                'total_outflows' => $cashFlow->sum('outflows'),
                'net_change' => $cashFlow->sum('net_change'),
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ]);
    }

    private function getAccountBalances(int $conjuntoConfigId, string $accountType, ?string $startDate = null, ?string $endDate = null)
    {
        return ChartOfAccounts::forConjunto($conjuntoConfigId)
            ->byType($accountType)
            ->active()
            ->with(['transactionEntries' => function ($query) use ($startDate, $endDate) {
                $query->whereHas('transaction', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'posted');

                    if ($startDate) {
                        $q->where('transaction_date', '>=', $startDate);
                    }

                    if ($endDate) {
                        $q->where('transaction_date', '<=', $endDate);
                    }
                });
            }])
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($startDate, $endDate) {
                $balance = $account->getBalance($startDate, $endDate);

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'full_name' => $account->full_name,
                    'account_type' => $account->account_type,
                    'nature' => $account->nature,
                    'balance' => $balance,
                ];
            })
            ->filter(fn ($account) => $account['balance'] != 0);
    }

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
            4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $months[$month] ?? 'Desconocido';
    }

    private function getAvailableMonths(): array
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
            4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
            7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
            10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];
    }
}
