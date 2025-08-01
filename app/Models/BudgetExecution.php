<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetExecution extends Model
{
    protected $fillable = [
        'budget_item_id',
        'period_month',
        'period_year',
        'budgeted_amount',
        'actual_amount',
        'variance_amount',
        'variance_percentage',
    ];

    protected $casts = [
        'budgeted_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'variance_amount' => 'decimal:2',
        'variance_percentage' => 'decimal:2',
    ];

    protected $appends = [
        'period_name',
        'variance_status',
        'execution_percentage',
    ];

    public function budgetItem(): BelongsTo
    {
        return $this->belongsTo(BudgetItem::class);
    }

    public function scopeByPeriod($query, int $month, int $year)
    {
        return $query->where('period_month', $month)
                    ->where('period_year', $year);
    }

    public function scopeByYear($query, int $year)
    {
        return $query->where('period_year', $year);
    }

    public function scopeByMonth($query, int $month)
    {
        return $query->where('period_month', $month);
    }

    public function getPeriodNameAttribute(): string
    {
        $months = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return $months[$this->period_month] . ' ' . $this->period_year;
    }

    public function getVarianceStatusAttribute(): string
    {
        if ($this->variance_amount == 0) {
            return 'exact';
        }

        return $this->variance_amount > 0 ? 'over' : 'under';
    }

    public function getExecutionPercentageAttribute(): float
    {
        if ($this->budgeted_amount == 0) {
            return 0;
        }

        return round(($this->actual_amount / $this->budgeted_amount) * 100, 2);
    }

    public function isOverBudget(): bool
    {
        return $this->actual_amount > $this->budgeted_amount;
    }

    public function isUnderBudget(): bool
    {
        return $this->actual_amount < $this->budgeted_amount;
    }

    public function isOnBudget(): bool
    {
        return $this->actual_amount == $this->budgeted_amount;
    }

    public static function getSummaryByPeriod(int $conjuntoConfigId, int $month, int $year): array
    {
        $executions = self::whereHas('budgetItem.budget', function ($query) use ($conjuntoConfigId) {
            $query->where('conjunto_config_id', $conjuntoConfigId)
                  ->where('status', 'active');
        })
        ->byPeriod($month, $year)
        ->with('budgetItem.account')
        ->get();

        return [
            'income' => $executions->where('budgetItem.category', 'income'),
            'expenses' => $executions->where('budgetItem.category', 'expense'),
            'total_income_budgeted' => $executions->where('budgetItem.category', 'income')->sum('budgeted_amount'),
            'total_income_actual' => $executions->where('budgetItem.category', 'income')->sum('actual_amount'),
            'total_expenses_budgeted' => $executions->where('budgetItem.category', 'expense')->sum('budgeted_amount'),
            'total_expenses_actual' => $executions->where('budgetItem.category', 'expense')->sum('actual_amount'),
            'net_budgeted' => $executions->where('budgetItem.category', 'income')->sum('budgeted_amount') - $executions->where('budgetItem.category', 'expense')->sum('budgeted_amount'),
            'net_actual' => $executions->where('budgetItem.category', 'income')->sum('actual_amount') - $executions->where('budgetItem.category', 'expense')->sum('actual_amount'),
        ];
    }

    public static function getYearToDateSummary(int $conjuntoConfigId, int $year): array
    {
        $executions = self::whereHas('budgetItem.budget', function ($query) use ($conjuntoConfigId) {
            $query->where('conjunto_config_id', $conjuntoConfigId)
                  ->where('status', 'active');
        })
        ->byYear($year)
        ->with('budgetItem.account')
        ->get();

        return [
            'income' => $executions->where('budgetItem.category', 'income'),
            'expenses' => $executions->where('budgetItem.category', 'expense'),
            'total_income_budgeted' => $executions->where('budgetItem.category', 'income')->sum('budgeted_amount'),
            'total_income_actual' => $executions->where('budgetItem.category', 'income')->sum('actual_amount'),
            'total_expenses_budgeted' => $executions->where('budgetItem.category', 'expense')->sum('budgeted_amount'),
            'total_expenses_actual' => $executions->where('budgetItem.category', 'expense')->sum('actual_amount'),
            'net_budgeted' => $executions->where('budgetItem.category', 'income')->sum('budgeted_amount') - $executions->where('budgetItem.category', 'expense')->sum('budgeted_amount'),
            'net_actual' => $executions->where('budgetItem.category', 'income')->sum('actual_amount') - $executions->where('budgetItem.category', 'expense')->sum('actual_amount'),
        ];
    }
}
