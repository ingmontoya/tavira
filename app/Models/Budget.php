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
        return $this->status === 'draft' && $this->items()->count() > 0;
    }

    public function getCanBeActivatedAttribute(): bool
    {
        return $this->status === 'approved';
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
}
