<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountingTransactionEntry extends Model
{
    protected $fillable = [
        'accounting_transaction_id',
        'account_id',
        'description',
        'debit_amount',
        'credit_amount',
        'third_party_type',
        'third_party_id',
        'cost_center_id',
    ];

    protected $casts = [
        'debit_amount' => 'decimal:2',
        'credit_amount' => 'decimal:2',
    ];

    protected $appends = [
        'is_debit',
        'is_credit',
        'amount',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(AccountingTransaction::class, 'accounting_transaction_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccounts::class, 'account_id');
    }

    public function thirdParty(): MorphTo
    {
        return $this->morphTo('third_party', 'third_party_type', 'third_party_id');
    }

    public function scopeDebits($query)
    {
        return $query->where('debit_amount', '>', 0);
    }

    public function scopeCredits($query)
    {
        return $query->where('credit_amount', '>', 0);
    }

    public function scopeByAccount($query, int $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeByThirdParty($query, string $type, int $id)
    {
        return $query->where('third_party_type', $type)
                    ->where('third_party_id', $id);
    }

    public function getIsDebitAttribute(): bool
    {
        return $this->debit_amount > 0;
    }

    public function getIsCreditAttribute(): bool
    {
        return $this->credit_amount > 0;
    }

    public function getAmountAttribute(): float
    {
        return $this->is_debit ? $this->debit_amount : $this->credit_amount;
    }

    public function getThirdPartyName(): ?string
    {
        if (!$this->third_party_type || !$this->third_party_id) {
            return null;
        }

        return match ($this->third_party_type) {
            'apartment' => $this->thirdParty?->number ?? "Apto #{$this->third_party_id}",
            'supplier' => $this->thirdParty?->name ?? "Proveedor #{$this->third_party_id}",
            'employee' => $this->thirdParty?->name ?? "Empleado #{$this->third_party_id}",
            default => "Tercero #{$this->third_party_id}",
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($entry) {
            if ($entry->debit_amount > 0 && $entry->credit_amount > 0) {
                throw new \InvalidArgumentException('Un movimiento no puede tener débito y crédito al mismo tiempo');
            }

            if ($entry->debit_amount <= 0 && $entry->credit_amount <= 0) {
                throw new \InvalidArgumentException('Un movimiento debe tener débito o crédito mayor a cero');
            }
        });

        static::updating(function ($entry) {
            if ($entry->debit_amount > 0 && $entry->credit_amount > 0) {
                throw new \InvalidArgumentException('Un movimiento no puede tener débito y crédito al mismo tiempo');
            }

            if ($entry->debit_amount <= 0 && $entry->credit_amount <= 0) {
                throw new \InvalidArgumentException('Un movimiento debe tener débito o crédito mayor a cero');
            }
        });
    }
}
