<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingPeriodClosure extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'fiscal_year',
        'period_type',
        'period_start_date',
        'period_end_date',
        'closure_date',
        'status',
        'total_income',
        'total_expenses',
        'net_result',
        'closing_transaction_id',
        'notes',
        'closed_by',
    ];

    protected $casts = [
        'period_start_date' => 'date',
        'period_end_date' => 'date',
        'closure_date' => 'date',
        'total_income' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'net_result' => 'decimal:2',
    ];

    protected $appends = [
        'status_label',
        'period_label',
        'is_profit',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function closingTransaction(): BelongsTo
    {
        return $this->belongsTo(AccountingTransaction::class, 'closing_transaction_id');
    }

    public function closedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Borrador',
            'completed' => 'Completado',
            'reversed' => 'Reversado',
            default => 'Sin estado',
        };
    }

    public function getPeriodLabelAttribute(): string
    {
        if ($this->period_type === 'monthly') {
            return $this->period_start_date->format('F Y');
        }

        return "Año {$this->fiscal_year}";
    }

    public function getIsProfitAttribute(): bool
    {
        return $this->net_result > 0;
    }

    public function scopeForConjunto($query, int $conjuntoConfigId)
    {
        return $query->where('conjunto_config_id', $conjuntoConfigId);
    }

    public function scopeByFiscalYear($query, int $fiscalYear)
    {
        return $query->where('fiscal_year', $fiscalYear);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function canBeReversed(): bool
    {
        return $this->status === 'completed';
    }

    public function canBeCompleted(): bool
    {
        return $this->status === 'draft';
    }
}
