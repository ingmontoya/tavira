<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'name',
        'fiscal_year',
        'start_date',
        'end_date',
        'total_budgeted_income',
        'total_budgeted_expenses',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_budgeted_income' => 'decimal:2',
        'total_budgeted_expenses' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'status_badge',
        'budget_balance',
        'can_be_approved',
        'can_be_activated',
        'can_approve',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(BudgetItem::class);
    }

    public function incomeItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class)->where('category', 'income');
    }

    public function expenseItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class)->where('category', 'expense');
    }

    public function fixedExpenseItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class)->where('category', 'expense')->where('expense_type', 'fixed');
    }

    public function variableExpenseItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class)->where('category', 'expense')->where('expense_type', 'variable');
    }

    public function specialFundItems(): HasMany
    {
        return $this->hasMany(BudgetItem::class)->where('category', 'expense')->where('expense_type', 'special_fund');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeForConjunto($query, int $conjuntoConfigId)
    {
        return $query->where('conjunto_config_id', $conjuntoConfigId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Borrador',
            'approved' => 'Aprobado',
            'active' => 'Activo',
            'closed' => 'Cerrado',
            default => 'Sin estado',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'draft' => ['text' => 'Borrador', 'class' => 'bg-yellow-100 text-yellow-800'],
            'approved' => ['text' => 'Aprobado', 'class' => 'bg-blue-100 text-blue-800'],
            'active' => ['text' => 'Activo', 'class' => 'bg-green-100 text-green-800'],
            'closed' => ['text' => 'Cerrado', 'class' => 'bg-gray-100 text-gray-800'],
            default => ['text' => 'Sin estado', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getBudgetBalanceAttribute(): float
    {
        return $this->total_budgeted_income - $this->total_budgeted_expenses;
    }

    public function getCanBeApprovedAttribute(): bool
    {
        // Use items_count if available (from withCount), otherwise fallback to query
        $itemsCount = $this->items_count ?? $this->items()->count();
        return $this->status === 'draft' && $itemsCount > 0;
    }

    public function getCanBeActivatedAttribute(): bool
    {
        return $this->status === 'approved';
    }

    public function getCanApproveAttribute(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        // Use items_count if available (from withCount), otherwise fallback to query
        $itemsCount = $this->items_count ?? $this->items()->count();

        // Only users with 'concejo' role can approve budgets
        return $user->hasRole('concejo') && $this->status === 'draft' && $itemsCount > 0;
    }

    public function calculateTotals(): void
    {
        $this->total_budgeted_income = $this->incomeItems()->sum('budgeted_amount');
        $this->total_budgeted_expenses = $this->expenseItems()->sum('budgeted_amount');
        $this->save();
    }

    public function approve(): void
    {
        if (! $this->can_be_approved) {
            throw new \Exception('El presupuesto no puede ser aprobado');
        }

        $user = auth()->user();
        if (! $user || ! $user->hasRole('concejo')) {
            throw new \Exception('Solo el Concejo de Administración puede aprobar presupuestos');
        }

        $this->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
    }

    public function activate(): void
    {
        if (! $this->can_be_activated) {
            throw new \Exception('El presupuesto no puede ser activado');
        }

        // Deactivate other active budgets for the same conjunto and year
        self::forConjunto($this->conjunto_config_id)
            ->byYear($this->fiscal_year)
            ->where('id', '!=', $this->id)
            ->update(['status' => 'closed']);

        $this->update(['status' => 'active']);

        // Create budget executions for all months
        $this->createBudgetExecutions();
    }

    public function close(): void
    {
        if ($this->status !== 'active') {
            throw new \Exception('Solo se pueden cerrar presupuestos activos');
        }

        $this->update(['status' => 'closed']);
    }

    private function createBudgetExecutions(): void
    {
        foreach ($this->items as $item) {
            $item->createMonthlyExecutions();
        }
    }

    public function getExecutionSummary(?int $month = null, ?int $year = null): array
    {
        $year = $year ?? $this->fiscal_year;
        $month = $month ?? now()->month;

        $executions = BudgetExecution::whereHas('budgetItem', function ($query) {
            $query->where('budget_id', $this->id);
        })
            ->when($month, fn ($q) => $q->where('period_month', $month))
            ->where('period_year', $year)
            ->with('budgetItem.account')
            ->get();

        return [
            'income' => $executions->where('budgetItem.category', 'income'),
            'expenses' => $executions->where('budgetItem.category', 'expense'),
            'total_income_budgeted' => $executions->where('budgetItem.category', 'income')->sum('budgeted_amount'),
            'total_income_actual' => $executions->where('budgetItem.category', 'income')->sum('actual_amount'),
            'total_expenses_budgeted' => $executions->where('budgetItem.category', 'expense')->sum('budgeted_amount'),
            'total_expenses_actual' => $executions->where('budgetItem.category', 'expense')->sum('actual_amount'),
        ];
    }

    public static function createFromPrevious(int $conjuntoConfigId, int $previousYear, int $newYear): self
    {
        $previousBudget = self::forConjunto($conjuntoConfigId)
            ->byYear($previousYear)
            ->with('items.account')
            ->first();

        if (! $previousBudget) {
            throw new \Exception('No se encontró presupuesto del año anterior');
        }

        $newBudget = self::create([
            'conjunto_config_id' => $conjuntoConfigId,
            'name' => "Presupuesto {$newYear}",
            'fiscal_year' => $newYear,
            'start_date' => "{$newYear}-01-01",
            'end_date' => "{$newYear}-12-31",
            'status' => 'draft',
        ]);

        foreach ($previousBudget->items as $item) {
            $newBudget->items()->create([
                'account_id' => $item->account_id,
                'category' => $item->category,
                'budgeted_amount' => $item->budgeted_amount,
                'notes' => $item->notes,
            ]);
        }

        $newBudget->calculateTotals();

        return $newBudget;
    }

    public static function createWithDefaultTemplate(int $conjuntoConfigId, string $name, int $fiscalYear, string $startDate, string $endDate): self
    {
        $budget = self::create([
            'conjunto_config_id' => $conjuntoConfigId,
            'name' => $name,
            'fiscal_year' => $fiscalYear,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => 'draft',
        ]);

        $budget->createDefaultBudgetItems();

        return $budget;
    }

    public function createDefaultBudgetItems(): void
    {
        $defaultAccounts = $this->getDefaultBudgetAccountsTemplate();

        foreach ($defaultAccounts as $accountData) {
            $account = ChartOfAccounts::forConjunto($this->conjunto_config_id)
                ->where('code', $accountData['code'])
                ->where('is_active', true)
                ->where('accepts_posting', true)
                ->first();

            if ($account) {
                $this->items()->create([
                    'account_id' => $account->id,
                    'category' => $accountData['category'],
                    'expense_type' => $accountData['expense_type'] ?? null,
                    'budgeted_amount' => $accountData['default_amount'] ?? 0,
                    'notes' => $accountData['notes'] ?? null,
                ]);
            }
        }

        $this->calculateTotals();
    }

    public function getCashFlowProjection(): array
    {
        $monthlyData = [];
        $cumulativeBalance = 0;

        for ($month = 1; $month <= 12; $month++) {
            $monthlyIncome = $this->incomeItems->sum(function ($item) use ($month) {
                return $item->getAmountForMonth($month);
            });

            $monthlyExpenses = $this->expenseItems->sum(function ($item) use ($month) {
                return $item->getAmountForMonth($month);
            });

            $netCashFlow = $monthlyIncome - $monthlyExpenses;
            $cumulativeBalance += $netCashFlow;

            $monthlyData[] = [
                'month' => $month,
                'month_name' => $this->getMonthName($month),
                'income' => (float) $monthlyIncome,
                'expenses' => (float) $monthlyExpenses,
                'net_cash_flow' => (float) $netCashFlow,
                'cumulative_balance' => (float) $cumulativeBalance,
            ];
        }

        return $monthlyData;
    }

    public function getExpensesSummaryByCategoryType(): array
    {
        return [
            'fixed' => [
                'total' => (float) $this->fixedExpenseItems->sum('budgeted_amount'),
                'items' => $this->fixedExpenseItems->with('account')->get(),
            ],
            'variable' => [
                'total' => (float) $this->variableExpenseItems->sum('budgeted_amount'),
                'items' => $this->variableExpenseItems->with('account')->get(),
            ],
            'special_fund' => [
                'total' => (float) $this->specialFundItems->sum('budgeted_amount'),
                'items' => $this->specialFundItems->with('account')->get(),
            ],
        ];
    }

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $months[$month] ?? '';
    }

    private function getDefaultBudgetAccountsTemplate(): array
    {
        return [
            // Note: Income accounts removed - users should add them manually
            // This allows for flexible income configuration per residential complex

            // Fixed Expense Accounts
            [
                'code' => '510501',
                'category' => 'expense',
                'expense_type' => 'fixed',
                'default_amount' => 0,
                'notes' => 'Gastos por sueldos y salarios del personal',
            ],
            [
                'code' => '513501',
                'category' => 'expense',
                'expense_type' => 'fixed',
                'default_amount' => 0,
                'notes' => 'Gastos por servicio de energía eléctrica',
            ],
            [
                'code' => '513502',
                'category' => 'expense',
                'expense_type' => 'fixed',
                'default_amount' => 0,
                'notes' => 'Gastos por servicios de agua y alcantarillado',
            ],
            [
                'code' => '513508',
                'category' => 'expense',
                'expense_type' => 'fixed',
                'default_amount' => 0,
                'notes' => 'Gastos por servicios de vigilancia',
            ],
            [
                'code' => '513509',
                'category' => 'expense',
                'expense_type' => 'variable',
                'default_amount' => 0,
                'notes' => 'Gastos por servicios de jardinería',
            ],
            [
                'code' => '513510',
                'category' => 'expense',
                'expense_type' => 'fixed',
                'default_amount' => 0,
                'notes' => 'Gastos por limpieza de zonas comunes',
            ],
            [
                'code' => '530501',
                'category' => 'expense',
                'expense_type' => 'fixed',
                'default_amount' => 0,
                'notes' => 'Gastos por servicios bancarios',
            ],
        ];
    }

    public function getBudgetAlerts(?int $month = null, ?int $year = null): array
    {
        $month = $month ?? now()->month;
        $year = $year ?? $this->fiscal_year;

        $executions = BudgetExecution::whereHas('budgetItem', function ($query) {
            $query->where('budget_id', $this->id);
        })
            ->byPeriod($month, $year)
            ->with(['budgetItem.account'])
            ->get();

        $alerts = [];
        foreach ($executions as $execution) {
            $alert = $execution->getVarianceAlert();
            if ($alert) {
                $alerts[] = array_merge($alert, [
                    'account' => $execution->budgetItem->account,
                    'execution' => $execution,
                ]);
            }
        }

        return $alerts;
    }

    public function hasActiveAlerts(?int $month = null, ?int $year = null): bool
    {
        return ! empty($this->getBudgetAlerts($month, $year));
    }

    public function getAlertsCount(?int $month = null, ?int $year = null): array
    {
        $alerts = $this->getBudgetAlerts($month, $year);

        return [
            'total' => count($alerts),
            'danger' => count(array_filter($alerts, fn ($alert) => $alert['type'] === 'danger')),
            'warning' => count(array_filter($alerts, fn ($alert) => $alert['type'] === 'warning')),
        ];
    }

    /**
     * Generate intelligent budget suggestions based on historical data
     * Returns suggested amounts for each account with explanation
     */
    public static function generateBudgetSuggestions(int $conjuntoConfigId, int $fiscalYear): array
    {
        $previousYear = $fiscalYear - 1;

        // Get previous budget if exists
        $previousBudget = self::forConjunto($conjuntoConfigId)
            ->byYear($previousYear)
            ->with('items.account')
            ->first();

        // Get actual accounting transactions from previous year
        $previousYearTransactions = AccountingTransaction::forConjunto($conjuntoConfigId)
            ->whereYear('posted_at', $previousYear)
            ->where('status', 'posted')
            ->with('entries.account')
            ->get();

        // Get budget execution data from previous year
        $previousExecution = null;
        if ($previousBudget) {
            $previousExecution = BudgetExecution::whereHas('budgetItem', function ($query) use ($previousBudget) {
                $query->where('budget_id', $previousBudget->id);
            })
                ->where('period_year', $previousYear)
                ->with('budgetItem.account')
                ->get();
        }

        $suggestions = [
            'income_accounts' => [],
            'expense_accounts' => [],
            'summary' => [
                'total_income_suggested' => 0,
                'total_expense_suggested' => 0,
                'net_surplus_suggested' => 0,
                'confidence_level' => 'high', // high, medium, low
            ],
            'methodology' => [],
        ];

        // Get all accounts
        $incomeAccounts = ChartOfAccounts::forConjunto($conjuntoConfigId)
            ->byType('income')
            ->postable()
            ->active()
            ->get();

        $expenseAccounts = ChartOfAccounts::forConjunto($conjuntoConfigId)
            ->byType('expense')
            ->postable()
            ->active()
            ->get();

        // Calculate suggestions for income accounts
        foreach ($incomeAccounts as $account) {
            $suggestion = self::calculateAccountSuggestion(
                $account,
                $previousBudget,
                $previousExecution,
                $previousYearTransactions,
                'income'
            );

            if ($suggestion['suggested_amount'] > 0) {
                $suggestions['income_accounts'][] = $suggestion;
                $suggestions['summary']['total_income_suggested'] += $suggestion['suggested_amount'];
            }
        }

        // Calculate suggestions for expense accounts
        foreach ($expenseAccounts as $account) {
            $suggestion = self::calculateAccountSuggestion(
                $account,
                $previousBudget,
                $previousExecution,
                $previousYearTransactions,
                'expense'
            );

            if ($suggestion['suggested_amount'] > 0) {
                $suggestions['expense_accounts'][] = $suggestion;
                $suggestions['summary']['total_expense_suggested'] += $suggestion['suggested_amount'];
            }
        }

        $suggestions['summary']['net_surplus_suggested'] =
            $suggestions['summary']['total_income_suggested'] -
            $suggestions['summary']['total_expense_suggested'];

        // Determine confidence level
        $hasHistoricalData = $previousBudget !== null || $previousYearTransactions->isNotEmpty();
        $suggestions['summary']['confidence_level'] = $hasHistoricalData ? 'high' : 'low';

        // Add methodology explanation
        if ($previousBudget && $previousExecution && $previousExecution->isNotEmpty()) {
            $suggestions['methodology'][] = '✓ Basado en ejecución presupuestal del año anterior';
        }
        if ($previousYearTransactions->isNotEmpty()) {
            $suggestions['methodology'][] = '✓ Basado en transacciones contables reales del año anterior';
        }
        if ($previousBudget) {
            $suggestions['methodology'][] = '✓ Ajustado por inflación estimada (5%)';
        }
        if (! $hasHistoricalData) {
            $suggestions['methodology'][] = '⚠ Sin datos históricos - sugerencias basadas en estructura típica de P.H.';
        }

        return $suggestions;
    }

    /**
     * Calculate suggestion for a specific account
     */
    private static function calculateAccountSuggestion(
        ChartOfAccounts $account,
        ?Budget $previousBudget,
        ?\Illuminate\Support\Collection $previousExecution,
        \Illuminate\Support\Collection $previousTransactions,
        string $category
    ): array {
        $suggestion = [
            'account_id' => $account->id,
            'account_code' => $account->code,
            'account_name' => $account->name,
            'category' => $category,
            'suggested_amount' => 0,
            'previous_budgeted' => 0,
            'previous_actual' => 0,
            'variance_percentage' => 0,
            'explanation' => '',
            'confidence' => 'medium',
        ];

        // Get previous budget amount for this account
        if ($previousBudget) {
            $previousItem = $previousBudget->items()
                ->where('account_id', $account->id)
                ->first();

            if ($previousItem) {
                $suggestion['previous_budgeted'] = $previousItem->budgeted_amount;
            }
        }

        // Get previous execution/actual amount
        if ($previousExecution) {
            $actualAmount = $previousExecution
                ->where('budgetItem.account_id', $account->id)
                ->sum('actual_amount');

            $suggestion['previous_actual'] = $actualAmount;
        }

        // Get actual transactions amount
        $transactionAmount = 0;
        foreach ($previousTransactions as $transaction) {
            foreach ($transaction->entries as $entry) {
                if ($entry->account_id == $account->id) {
                    // For income, credits increase; for expenses, debits increase
                    if ($category === 'income') {
                        $transactionAmount += $entry->credit_amount;
                    } else {
                        $transactionAmount += $entry->debit_amount;
                    }
                }
            }
        }

        // Determine best source for suggestion
        $inflationRate = 1.05; // 5% inflation adjustment

        if ($suggestion['previous_actual'] > 0) {
            // Use actual execution as primary source
            $suggestion['suggested_amount'] = round($suggestion['previous_actual'] * $inflationRate, 2);
            $suggestion['confidence'] = 'high';
            $suggestion['explanation'] = sprintf(
                'Basado en ejecución real año anterior ($%s) + 5%% inflación',
                number_format($suggestion['previous_actual'], 0)
            );
        } elseif ($transactionAmount > 0) {
            // Use transactions as secondary source
            $suggestion['suggested_amount'] = round($transactionAmount * $inflationRate, 2);
            $suggestion['confidence'] = 'high';
            $suggestion['explanation'] = sprintf(
                'Basado en transacciones contables año anterior ($%s) + 5%% inflación',
                number_format($transactionAmount, 0)
            );
        } elseif ($suggestion['previous_budgeted'] > 0) {
            // Use previous budget as tertiary source
            $suggestion['suggested_amount'] = round($suggestion['previous_budgeted'] * $inflationRate, 2);
            $suggestion['confidence'] = 'medium';
            $suggestion['explanation'] = sprintf(
                'Basado en presupuesto año anterior ($%s) + 5%% inflación',
                number_format($suggestion['previous_budgeted'], 0)
            );
        }

        // Calculate variance if we have both budgeted and actual
        if ($suggestion['previous_budgeted'] > 0 && $suggestion['previous_actual'] > 0) {
            $suggestion['variance_percentage'] = round(
                (($suggestion['previous_actual'] - $suggestion['previous_budgeted']) / $suggestion['previous_budgeted']) * 100,
                1
            );
        }

        return $suggestion;
    }
}
