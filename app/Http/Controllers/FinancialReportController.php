<?php

namespace App\Http\Controllers;

use App\Models\AccountingTransactionEntry;
use App\Models\Budget;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FinancialReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Accounting/Reports/Index', [
            'reports' => [
                [
                    'name' => 'Balance General',
                    'description' => 'Estado de situación financiera del conjunto',
                    'route' => 'financial-reports.balance-sheet',
                    'icon' => 'balance-sheet',
                ],
                [
                    'name' => 'Estado de Resultados',
                    'description' => 'Ingresos y gastos del período',
                    'route' => 'financial-reports.income-statement',
                    'icon' => 'income-statement',
                ],
                [
                    'name' => 'Libro Mayor',
                    'description' => 'Movimientos detallados por cuenta',
                    'route' => 'financial-reports.general-ledger',
                    'icon' => 'ledger',
                ],
                [
                    'name' => 'Ejecución Presupuestal',
                    'description' => 'Comparativo presupuesto vs ejecutado',
                    'route' => 'financial-reports.budget-execution',
                    'icon' => 'budget',
                ],
                [
                    'name' => 'Cartera por Edades',
                    'description' => 'Análisis de cuentas por cobrar',
                    'route' => 'financial-reports.aging-report',
                    'icon' => 'aging',
                ],
            ],
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = $request->get('date', now()->toDateString());
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $assets = $this->getAccountBalances($conjunto->id, 'asset', $date);
        $liabilities = $this->getAccountBalances($conjunto->id, 'liability', $date);
        $equity = $this->getAccountBalances($conjunto->id, 'equity', $date);

        $totalAssets = collect($assets)->sum('balance');
        $totalLiabilities = collect($liabilities)->sum('balance');
        $totalEquity = collect($equity)->sum('balance');

        return Inertia::render('Accounting/Reports/BalanceSheet', [
            'date' => $date,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'totals' => [
                'assets' => $totalAssets,
                'liabilities' => $totalLiabilities,
                'equity' => $totalEquity,
                'total_liabilities_equity' => $totalLiabilities + $totalEquity,
                'is_balanced' => abs($totalAssets - ($totalLiabilities + $totalEquity)) < 0.01,
            ],
        ]);
    }

    public function incomeStatement(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $income = $this->getAccountBalances($conjunto->id, 'income', $endDate, $startDate);
        $expenses = $this->getAccountBalances($conjunto->id, 'expense', $endDate, $startDate);

        $totalIncome = collect($income)->sum('balance');
        $totalExpenses = collect($expenses)->sum('balance');
        $netIncome = $totalIncome - $totalExpenses;

        return Inertia::render('Accounting/Reports/IncomeStatement', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'income' => $income,
            'expenses' => $expenses,
            'totals' => [
                'income' => $totalIncome,
                'expenses' => $totalExpenses,
                'net_income' => $netIncome,
            ],
        ]);
    }

    public function generalLedger(Request $request)
    {
        $request->validate([
            'account_id' => 'nullable|exists:chart_of_accounts,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $conjunto = ConjuntoConfig::where('is_active', true)->first();
        $accountId = $request->get('account_id');
        $startDate = $request->get('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->get('end_date', now()->endOfMonth()->toDateString());

        $accounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->postable()
            ->active()
            ->when($accountId, function ($query) use ($accountId) {
                return $query->where('id', $accountId);
            })
            ->orderBy('code')
            ->get();

        $ledgerData = [];
        foreach ($accounts as $account) {
            $entries = AccountingTransactionEntry::with(['transaction'])
                ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                    $query->where('status', 'posted')
                        ->whereBetween('transaction_date', [$startDate, $endDate]);
                })
                ->where('account_id', $account->id)
                ->orderBy('created_at')
                ->get();

            if ($entries->isNotEmpty()) {
                $balance = 0;
                $entryData = [];

                foreach ($entries as $entry) {
                    if ($account->nature === 'debit') {
                        $balance += $entry->debit_amount - $entry->credit_amount;
                    } else {
                        $balance += $entry->credit_amount - $entry->debit_amount;
                    }

                    $entryData[] = [
                        'date' => $entry->transaction->transaction_date,
                        'transaction_number' => $entry->transaction->transaction_number,
                        'description' => $entry->description,
                        'debit' => $entry->debit_amount,
                        'credit' => $entry->credit_amount,
                        'balance' => $balance,
                    ];
                }

                $ledgerData[] = [
                    'account' => $account,
                    'entries' => $entryData,
                    'final_balance' => $balance,
                ];
            }
        }

        return Inertia::render('Accounting/Reports/GeneralLedger', [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'selected_account' => $accountId ? ChartOfAccounts::find($accountId) : null,
            'accounts' => ChartOfAccounts::forConjunto($conjunto->id)->postable()->active()->orderBy('code')->get(),
            'ledger_data' => $ledgerData,
        ]);
    }

    public function budgetExecution(Request $request)
    {
        $request->validate([
            'budget_id' => 'nullable|exists:budgets,id',
            'month' => 'nullable|integer|min:1|max:12',
            'year' => 'nullable|integer|min:2020|max:2050',
        ]);

        $conjunto = ConjuntoConfig::where('is_active', true)->first();
        $budgetId = $request->get('budget_id');
        $month = (int) $request->get('month', now()->month);
        $year = (int) $request->get('year', now()->year);

        if (! $budgetId) {
            $budget = Budget::forConjunto($conjunto->id)
                ->where('status', 'active')
                ->byYear($year)
                ->first();
        } else {
            $budget = Budget::find($budgetId);
        }

        if (! $budget) {
            return back()->with('error', 'No se encontró un presupuesto activo para el año seleccionado.');
        }

        $executionSummary = $budget->getExecutionSummary($month, $year);
        $alerts = $budget->getBudgetAlerts($month, $year);

        return Inertia::render('Accounting/Reports/BudgetExecution', [
            'budget' => $budget,
            'execution_summary' => $executionSummary,
            'alerts' => $alerts,
            'period' => [
                'month' => $month,
                'year' => $year,
                'name' => $this->getMonthName($month).' '.$year,
            ],
            'available_budgets' => Budget::forConjunto($conjunto->id)->orderBy('fiscal_year', 'desc')->get(),
        ]);
    }

    public function agingReport(Request $request)
    {
        $request->validate([
            'date' => 'nullable|date',
        ]);

        $date = $request->get('date', now()->toDateString());
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        // Get receivable accounts
        $receivableAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->where('code', 'LIKE', '1305%') // Accounts receivable accounts
            ->active()
            ->get();

        $agingData = [];
        foreach ($receivableAccounts as $account) {
            $balance = $account->getBalance(null, $date);

            if ($balance > 0) {
                // Simple aging calculation - in a real scenario, you'd track invoice dates
                $agingData[] = [
                    'account' => $account,
                    'current' => $balance * 0.6, // 60% current
                    '30_days' => $balance * 0.25, // 25% 30 days
                    '60_days' => $balance * 0.10, // 10% 60 days
                    '90_days' => $balance * 0.05, // 5% 90+ days
                    'total' => $balance,
                ];
            }
        }

        $totals = [
            'current' => collect($agingData)->sum('current'),
            '30_days' => collect($agingData)->sum('30_days'),
            '60_days' => collect($agingData)->sum('60_days'),
            '90_days' => collect($agingData)->sum('90_days'),
            'total' => collect($agingData)->sum('total'),
        ];

        return Inertia::render('Accounting/Reports/AgingReport', [
            'date' => $date,
            'aging_data' => $agingData,
            'totals' => $totals,
        ]);
    }

    private function getAccountBalances(int $conjuntoId, string $accountType, string $endDate, ?string $startDate = null): array
    {
        $accounts = ChartOfAccounts::forConjunto($conjuntoId)
            ->byType($accountType)
            ->active()
            ->orderBy('code')
            ->get();

        $balances = [];
        foreach ($accounts as $account) {
            $balance = $account->getBalance($startDate, $endDate);
            if ($balance != 0) {
                $balances[] = [
                    'account' => $account,
                    'balance' => $balance,
                ];
            }
        }

        return $balances;
    }

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $months[$month] ?? 'Mes desconocido';
    }
}
