<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExpenseCategory extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conjunto_config_id',
        'name',
        'description',
        'default_debit_account_id',
        'default_credit_account_id',
        'is_active',
        'color',
        'icon',
        'requires_approval',
        'budget_account_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_approval' => 'boolean',
    ];

    protected $appends = [
        'total_expenses',
        'total_budget',
        'budget_execution_percentage',
    ];

    // Relationships
    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function defaultDebitAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccounts::class, 'default_debit_account_id');
    }

    public function defaultCreditAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccounts::class, 'default_credit_account_id');
    }

    public function budgetAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccounts::class, 'budget_account_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    // Scopes
    public function scopeForConjunto($query, int $conjuntoConfigId)
    {
        return $query->where('conjunto_config_id', $conjuntoConfigId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithApproval($query)
    {
        return $query->where('requires_approval', true);
    }

    // Attributes
    public function getTotalExpensesAttribute(): float
    {
        return $this->expenses()
            ->whereIn('status', ['aprobado', 'pagado'])
            ->sum('total_amount');
    }

    public function getTotalBudgetAttribute(): float
    {
        if (! $this->budget_account_id) {
            return 0;
        }

        // Get current year budget for this category
        $currentYear = now()->year;

        return BudgetItem::whereHas('budget', function ($query) use ($currentYear) {
            $query->where('year', $currentYear)
                ->where('conjunto_config_id', $this->conjunto_config_id);
        })
            ->where('account_id', $this->budget_account_id)
            ->sum('annual_amount');
    }

    public function getBudgetExecutionPercentageAttribute(): float
    {
        $budget = $this->total_budget;

        if ($budget == 0) {
            return 0;
        }

        return round(($this->total_expenses / $budget) * 100, 2);
    }

    // Methods
    public function getExpensesForPeriod(string $startDate, string $endDate): \Illuminate\Database\Eloquent\Collection
    {
        return $this->expenses()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->whereIn('status', ['aprobado', 'pagado'])
            ->get();
    }

    public function getTotalForPeriod(string $startDate, string $endDate): float
    {
        return $this->expenses()
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->whereIn('status', ['aprobado', 'pagado'])
            ->sum('total_amount');
    }

    public function getMonthlyExpenses(?int $year = null): array
    {
        $year = $year ?? now()->year;
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyData[$month] = $this->expenses()
                ->whereYear('expense_date', $year)
                ->whereMonth('expense_date', $month)
                ->whereIn('status', ['aprobado', 'pagado'])
                ->sum('total_amount');
        }

        return $monthlyData;
    }

    public function canBeDeleted(): bool
    {
        return $this->expenses()->count() === 0;
    }

    public static function getDefaultCategories(): array
    {
        return [
            [
                'name' => 'Servicios Públicos',
                'description' => 'Gastos en energía eléctrica, agua, gas, etc.',
                'color' => '#3B82F6',
                'icon' => 'bolt',
                'requires_approval' => false,
            ],
            [
                'name' => 'Personal',
                'description' => 'Sueldos, salarios y prestaciones sociales',
                'color' => '#10B981',
                'icon' => 'users',
                'requires_approval' => true,
            ],
            [
                'name' => 'Mantenimiento',
                'description' => 'Reparaciones y mantenimiento de instalaciones',
                'color' => '#F59E0B',
                'icon' => 'wrench-screwdriver',
                'requires_approval' => true,
            ],
            [
                'name' => 'Vigilancia',
                'description' => 'Servicios de seguridad y vigilancia',
                'color' => '#EF4444',
                'icon' => 'shield-check',
                'requires_approval' => false,
            ],
            [
                'name' => 'Limpieza',
                'description' => 'Servicios de aseo y limpieza',
                'color' => '#8B5CF6',
                'icon' => 'sparkles',
                'requires_approval' => false,
            ],
            [
                'name' => 'Jardinería',
                'description' => 'Mantenimiento de zonas verdes y jardines',
                'color' => '#22C55E',
                'icon' => 'leaf',
                'requires_approval' => false,
            ],
            [
                'name' => 'Administrativos',
                'description' => 'Gastos administrativos generales',
                'color' => '#6B7280',
                'icon' => 'document-text',
                'requires_approval' => true,
            ],
            [
                'name' => 'Otros Gastos',
                'description' => 'Gastos diversos no clasificados',
                'color' => '#9CA3AF',
                'icon' => 'ellipsis-horizontal',
                'requires_approval' => true,
            ],
        ];
    }

    public static function createDefaultCategories(int $conjuntoConfigId): void
    {
        $categories = self::getDefaultCategories();

        foreach ($categories as $categoryData) {
            self::create([
                'conjunto_config_id' => $conjuntoConfigId,
                'name' => $categoryData['name'],
                'description' => $categoryData['description'],
                'is_active' => true,
                'color' => $categoryData['color'],
                'icon' => $categoryData['icon'],
                'requires_approval' => $categoryData['requires_approval'],
            ]);
        }
    }
}
