<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetItem extends Model
{
    protected $fillable = [
        'budget_id',
        'account_id',
        'category',
        'expense_type',
        'budgeted_amount',
        'jan_amount',
        'feb_amount',
        'mar_amount',
        'apr_amount',
        'may_amount',
        'jun_amount',
        'jul_amount',
        'aug_amount',
        'sep_amount',
        'oct_amount',
        'nov_amount',
        'dec_amount',
        'notes',
    ];

    protected $casts = [
        'budgeted_amount' => 'decimal:2',
        'jan_amount' => 'decimal:2',
        'feb_amount' => 'decimal:2',
        'mar_amount' => 'decimal:2',
        'apr_amount' => 'decimal:2',
        'may_amount' => 'decimal:2',
        'jun_amount' => 'decimal:2',
        'jul_amount' => 'decimal:2',
        'aug_amount' => 'decimal:2',
        'sep_amount' => 'decimal:2',
        'oct_amount' => 'decimal:2',
        'nov_amount' => 'decimal:2',
        'dec_amount' => 'decimal:2',
    ];

    protected $appends = [
        'category_label',
        'expense_type_label',
        'monthly_amounts',
        'total_distributed',
        'remaining_amount',
    ];

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccounts::class);
    }

    public function executions(): HasMany
    {
        return $this->hasMany(BudgetExecution::class);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeIncome($query)
    {
        return $query->where('category', 'income');
    }

    public function scopeExpense($query)
    {
        return $query->where('category', 'expense');
    }

    public function scopeByExpenseType($query, string $expenseType)
    {
        return $query->where('expense_type', $expenseType);
    }

    public function scopeFixedExpenses($query)
    {
        return $query->where('category', 'expense')->where('expense_type', 'fixed');
    }

    public function scopeVariableExpenses($query)
    {
        return $query->where('category', 'expense')->where('expense_type', 'variable');
    }

    public function scopeSpecialFunds($query)
    {
        return $query->where('category', 'expense')->where('expense_type', 'special_fund');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'income' => 'Ingreso',
            'expense' => 'Gasto',
            default => 'Sin categoría',
        };
    }

    public function getExpenseTypeLabelAttribute(): ?string
    {
        if ($this->category !== 'expense' || !$this->expense_type) {
            return null;
        }

        return match ($this->expense_type) {
            'fixed' => 'Gasto Fijo',
            'variable' => 'Gasto Variable',
            'special_fund' => 'Fondo Especial',
            default => null,
        };
    }

    public function getMonthlyAmountsAttribute(): array
    {
        return [
            1 => $this->jan_amount,
            2 => $this->feb_amount,
            3 => $this->mar_amount,
            4 => $this->apr_amount,
            5 => $this->may_amount,
            6 => $this->jun_amount,
            7 => $this->jul_amount,
            8 => $this->aug_amount,
            9 => $this->sep_amount,
            10 => $this->oct_amount,
            11 => $this->nov_amount,
            12 => $this->dec_amount,
        ];
    }

    public function getTotalDistributedAttribute(): float
    {
        return array_sum($this->monthly_amounts);
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->budgeted_amount - $this->total_distributed;
    }

    public function getAmountForMonth(int $month): float
    {
        return $this->monthly_amounts[$month] ?? 0;
    }

    public function setAmountForMonth(int $month, float $amount): void
    {
        $monthColumns = [
            1 => 'jan_amount',
            2 => 'feb_amount',
            3 => 'mar_amount',
            4 => 'apr_amount',
            5 => 'may_amount',
            6 => 'jun_amount',
            7 => 'jul_amount',
            8 => 'aug_amount',
            9 => 'sep_amount',
            10 => 'oct_amount',
            11 => 'nov_amount',
            12 => 'dec_amount',
        ];

        if (isset($monthColumns[$month])) {
            $this->{$monthColumns[$month]} = $amount;
            $this->save();
        }
    }

    public function distributeEqually(): void
    {
        $monthlyAmount = round($this->budgeted_amount / 12, 2);
        $remainder = $this->budgeted_amount - ($monthlyAmount * 12);

        $this->update([
            'jan_amount' => $monthlyAmount + ($remainder > 0 ? 0.01 : 0),
            'feb_amount' => $monthlyAmount,
            'mar_amount' => $monthlyAmount,
            'apr_amount' => $monthlyAmount,
            'may_amount' => $monthlyAmount,
            'jun_amount' => $monthlyAmount,
            'jul_amount' => $monthlyAmount,
            'aug_amount' => $monthlyAmount,
            'sep_amount' => $monthlyAmount,
            'oct_amount' => $monthlyAmount,
            'nov_amount' => $monthlyAmount,
            'dec_amount' => $monthlyAmount,
        ]);
    }

    public function createMonthlyExecutions(): void
    {
        for ($month = 1; $month <= 12; $month++) {
            BudgetExecution::updateOrCreate(
                [
                    'budget_item_id' => $this->id,
                    'period_month' => $month,
                    'period_year' => $this->budget->fiscal_year,
                ],
                [
                    'budgeted_amount' => $this->getAmountForMonth($month),
                    'actual_amount' => 0,
                    'variance_amount' => 0,
                    'variance_percentage' => 0,
                ]
            );
        }
    }

    public function updateExecution(int $month, int $year, float $actualAmount): void
    {
        $execution = BudgetExecution::where('budget_item_id', $this->id)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->first();

        if ($execution) {
            $varianceAmount = $actualAmount - $execution->budgeted_amount;
            $variancePercentage = $execution->budgeted_amount > 0
                ? ($varianceAmount / $execution->budgeted_amount) * 100
                : 0;

            $execution->update([
                'actual_amount' => $actualAmount,
                'variance_amount' => $varianceAmount,
                'variance_percentage' => round($variancePercentage, 2),
            ]);
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($budgetItem) {
            $budgetItem->budget->calculateTotals();
        });

        static::updated(function ($budgetItem) {
            $budgetItem->budget->calculateTotals();
        });

        static::deleted(function ($budgetItem) {
            $budgetItem->budget->calculateTotals();
        });
    }
}
