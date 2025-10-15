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
                'journal-book' => [
                    'name' => 'Libro Diario',
                    'description' => 'Registro cronológico de transacciones',
                    'icon' => 'calendar',
                ],
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

    public function journalBook(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:all,borrador,contabilizado,cancelado',
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();
        $status = $validated['status'] ?? 'contabilizado';

        // Convert 'all' to empty string for filtering
        if ($status === 'all') {
            $status = '';
        }

        // Get transactions for the period
        $query = \App\Models\AccountingTransaction::forConjunto($conjunto->id)
            ->with(['entries.account', 'createdBy', 'postedBy'])
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->orderBy('id');

        if ($status) {
            $query->byStatus($status);
        }

        $transactions = $query->get()->map(function ($transaction) {
            return [
                'id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'transaction_date' => $transaction->transaction_date->format('Y-m-d'),
                'description' => $transaction->description,
                'reference_type' => $transaction->reference_type,
                'status' => $transaction->status,
                'status_label' => $transaction->status_label,
                'total_debit' => $transaction->total_debit,
                'total_credit' => $transaction->total_credit,
                'is_balanced' => $transaction->is_balanced,
                'created_by' => $transaction->createdBy ? [
                    'id' => $transaction->createdBy->id,
                    'name' => $transaction->createdBy->name,
                ] : null,
                'posted_by' => $transaction->postedBy ? [
                    'id' => $transaction->postedBy->id,
                    'name' => $transaction->postedBy->name,
                ] : null,
                'posted_at' => $transaction->posted_at ? $transaction->posted_at->format('Y-m-d H:i:s') : null,
                'entries' => $transaction->entries->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'account' => [
                            'id' => $entry->account->id,
                            'code' => $entry->account->code,
                            'name' => $entry->account->name,
                            'full_name' => $entry->account->full_name,
                        ],
                        'description' => $entry->description,
                        'debit_amount' => $entry->debit_amount,
                        'credit_amount' => $entry->credit_amount,
                    ];
                }),
            ];
        });

        // Calculate totals
        $totalDebits = $transactions->sum('total_debit');
        $totalCredits = $transactions->sum('total_credit');

        return Inertia::render('Accounting/Reports/JournalBook', [
            'report' => [
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'description' => 'Del '.date('d/m/Y', strtotime($startDate)).' al '.date('d/m/Y', strtotime($endDate)),
                ],
                'transactions' => $transactions,
                'total_debits' => $totalDebits,
                'total_credits' => $totalCredits,
                'is_balanced' => abs($totalDebits - $totalCredits) < 0.01,
                'transaction_count' => $transactions->count(),
            ],
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => $status,
            ],
            'statuses' => [
                'borrador' => 'Borrador',
                'contabilizado' => 'Contabilizado',
                'cancelado' => 'Cancelado',
            ],
        ]);
    }

    public function balanceSheet(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'as_of_date' => 'nullable|date',
        ]);

        $asOfDate = $validated['as_of_date'] ?? now()->toDateString();

        // Assets
        $assets = $this->getAccountBalances($conjunto->id, 'asset', null, $asOfDate);
        $totalAssets = array_sum(array_column($assets, 'balance'));

        // Liabilities
        $liabilities = $this->getAccountBalances($conjunto->id, 'liability', null, $asOfDate);
        $totalLiabilities = array_sum(array_column($liabilities, 'balance'));

        // Equity
        $equity = $this->getAccountBalances($conjunto->id, 'equity', null, $asOfDate);
        $totalEquity = array_sum(array_column($equity, 'balance'));

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
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();

        // Income - Solo incluir ingresos de pagos efectivamente recibidos
        $income = $this->getCashBasisIncome($conjunto->id, $startDate, $endDate);
        $totalIncome = array_sum(array_column($income, 'balance'));

        // Expenses
        $expenses = $this->getAccountBalances($conjunto->id, 'expense', $startDate, $endDate);
        $totalExpenses = array_sum(array_column($expenses, 'balance'));

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
            'as_of_date' => 'nullable|date',
        ]);

        $asOfDate = $validated['as_of_date'] ?? now()->toDateString();

        $accounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->active()
            ->with(['transactionEntries' => function ($query) use ($asOfDate) {
                $query->whereHas('transaction', function ($q) use ($asOfDate) {
                    $q->where('status', 'contabilizado')
                        ->where('transaction_date', '<=', $asOfDate);
                });
            }])
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($asOfDate) {
                // Balance de Prueba usa contabilidad por causación (base devengado)
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

        $accountsArray = $accounts->toArray();
        $totalDebits = array_sum(array_column($accountsArray, 'debit_balance'));
        $totalCredits = array_sum(array_column($accountsArray, 'credit_balance'));

        return Inertia::render('Accounting/Reports/TrialBalance', [
            'report' => [
                'as_of_date' => $asOfDate,
                'accounts' => $accountsArray,
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
            'account_id' => 'nullable|exists:chart_of_accounts,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // Get default account if none provided
        $accountId = $validated['account_id'] ?? null;
        if (! $accountId) {
            // Find a postable account that has transaction entries
            $accountIds = \App\Models\AccountingTransactionEntry::whereHas('transaction', function ($q) use ($conjunto) {
                $q->where('status', 'contabilizado')
                    ->where('conjunto_config_id', $conjunto->id);
            })->distinct()->pluck('account_id');

            $defaultAccount = ChartOfAccounts::forConjunto($conjunto->id)
                ->whereIn('id', $accountIds)
                ->postable()
                ->active()
                ->orderBy('code')
                ->first();

            if (! $defaultAccount) {
                return back()->withErrors(['account' => 'No hay cuentas contables con movimientos disponibles.']);
            }
            $accountId = $defaultAccount->id;
        }

        $account = ChartOfAccounts::forConjunto($conjunto->id)->findOrFail($accountId);

        // If no date filters provided, use a range that includes all transactions for this account
        if (! isset($validated['start_date']) && ! isset($validated['end_date'])) {
            $transactionDates = $account->transactionEntries()
                ->whereHas('transaction', function ($q) use ($conjunto) {
                    $q->where('status', 'contabilizado')
                        ->where('conjunto_config_id', $conjunto->id);
                })
                ->with('transaction:id,transaction_date')
                ->get()
                ->pluck('transaction.transaction_date')
                ->filter();

            if ($transactionDates->isNotEmpty()) {
                $startDate = $transactionDates->min()->toDateString();
                $endDate = $transactionDates->max()->toDateString();
            } else {
                $startDate = now()->startOfYear()->toDateString();
                $endDate = now()->toDateString();
            }
        } else {
            $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
            $endDate = $validated['end_date'] ?? now()->toDateString();
        }

        // Get opening balance
        $openingBalance = $account->getBalance(null, date('Y-m-d', strtotime($startDate.' -1 day')));

        // Get transactions for the period
        $entries = $account->transactionEntries()
            ->whereHas('transaction', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'contabilizado')
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
                'account_id' => $accountId,
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
        $budgetId = $validated['budget_id'] ?? null;
        if ($budgetId) {
            $budget = Budget::forConjunto($conjunto->id)->findOrFail($budgetId);
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
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $startDate = $validated['start_date'] ?? now()->startOfMonth()->toDateString();
        $endDate = $validated['end_date'] ?? now()->toDateString();

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
                    $query->where('status', 'contabilizado')
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

    private function getCashBasisIncome(int $conjuntoConfigId, string $startDate, string $endDate)
    {
        // Obtener ingresos basados en los montos de aplicaciones de pago
        $incomeAccounts = ChartOfAccounts::forConjunto($conjuntoConfigId)
            ->byType('income')
            ->active()
            ->orderBy('code')
            ->get()
            ->map(function ($account) use ($startDate, $endDate) {
                // Calcular ingresos de esta cuenta basado en aplicaciones de pago
                $cashBasisBalance = $account->transactionEntries()
                    ->whereHas('transaction', function ($q) {
                        $q->where('status', 'contabilizado')
                            ->where('reference_type', 'invoice');
                    })
                    ->get()
                    ->sum(function ($entry) use ($startDate, $endDate) {
                        // Por cada entry de factura, calcular qué proporción fue pagada en el período
                        $invoice = $entry->transaction->reference;
                        if (! $invoice) {
                            return 0;
                        }

                        $paymentsInPeriod = $invoice->paymentApplications()
                            ->where('status', 'activo')
                            ->whereBetween('applied_date', [$startDate, $endDate])
                            ->sum('amount_applied');

                        if ($paymentsInPeriod == 0) {
                            return 0;
                        }

                        // Calcular la proporción de este entry que fue pagada
                        $entryAmount = $entry->credit_amount - $entry->debit_amount;
                        $proportion = $paymentsInPeriod / $invoice->total_amount;

                        return $entryAmount * $proportion;
                    });

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'full_name' => $account->full_name,
                    'account_type' => $account->account_type,
                    'nature' => $account->nature,
                    'balance' => round($cashBasisBalance, 2),
                ];
            })
            ->filter(fn ($account) => $account['balance'] > 0)
            ->values()
            ->toArray();

        return $incomeAccounts;
    }

    private function getCashBasisBalance(ChartOfAccounts $account, string $asOfDate): float
    {
        // Para cuentas de ingreso: solo reconocer ingresos de facturas que han sido cobradas
        if ($account->account_type === 'income') {
            return $account->transactionEntries()
                ->whereHas('transaction', function ($q) use ($asOfDate) {
                    $q->where('status', 'contabilizado')
                        ->where('reference_type', 'invoice')
                        ->where('transaction_date', '<=', $asOfDate);
                })
                ->get()
                ->sum(function ($entry) use ($asOfDate) {
                    $invoice = $entry->transaction->reference;
                    if (! $invoice) {
                        return 0;
                    }

                    // Calcular qué proporción de esta factura fue cobrada hasta la fecha
                    $totalPaid = $invoice->paymentApplications()
                        ->where('status', 'activo')
                        ->where('applied_date', '<=', $asOfDate)
                        ->sum('amount_applied');

                    if ($totalPaid == 0) {
                        return 0;
                    }

                    $entryAmount = $entry->credit_amount - $entry->debit_amount;
                    $proportion = min(1, $totalPaid / $invoice->total_amount);

                    return $entryAmount * $proportion;
                });
        }

        // Para cuentas de cartera (130501): representa facturas pendientes de pago
        if ($account->code === '130501') {
            // La cartera siempre representa facturas emitidas pendientes de cobro
            // independientemente del método contable (caja vs devengado)
            $totalInvoiced = $account->transactionEntries()
                ->whereHas('transaction', function ($q) use ($asOfDate) {
                    $q->where('status', 'contabilizado')
                        ->where('reference_type', 'invoice')
                        ->where('transaction_date', '<=', $asOfDate);
                })
                ->sum('debit_amount');

            $totalCollected = $account->transactionEntries()
                ->whereHas('transaction', function ($q) use ($asOfDate) {
                    $q->where('status', 'contabilizado')
                        ->where('reference_type', 'payment_application')
                        ->where('transaction_date', '<=', $asOfDate);
                })
                ->sum('credit_amount');

            return $totalInvoiced - $totalCollected;
        }

        // Para el resto de cuentas: usar balance normal
        return $account->getBalance(null, $asOfDate);
    }

    private function getAccountBalances(int $conjuntoConfigId, string $accountType, ?string $startDate = null, ?string $endDate = null)
    {
        return ChartOfAccounts::forConjunto($conjuntoConfigId)
            ->byType($accountType)
            ->active()
            ->with(['transactionEntries' => function ($query) use ($startDate, $endDate) {
                $query->whereHas('transaction', function ($q) use ($startDate, $endDate) {
                    $q->where('status', 'contabilizado');

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
            ->filter(fn ($account) => $account['balance'] != 0)
            ->values()
            ->toArray(); // Convierte Collection a array PHP
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
