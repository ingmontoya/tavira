<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccounts extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'code',
        'name',
        'description',
        'account_type',
        'parent_id',
        'level',
        'is_active',
        'requires_third_party',
        'nature',
        'accepts_posting',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_third_party' => 'boolean',
        'accepts_posting' => 'boolean',
    ];

    protected $appends = [
        'account_type_label',
        'nature_label',
        'full_name',
        'has_children',
        'balance',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccounts::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccounts::class, 'parent_id');
    }

    public function transactionEntries(): HasMany
    {
        return $this->hasMany(AccountingTransactionEntry::class, 'account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('account_type', $type);
    }

    public function scopeByLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    public function scopePostable($query)
    {
        return $query->where('accepts_posting', true);
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeForConjunto($query, int $conjuntoConfigId)
    {
        return $query->where('conjunto_config_id', $conjuntoConfigId);
    }

    public function getAccountTypeLabelAttribute(): string
    {
        return match ($this->account_type) {
            'asset' => 'Activo',
            'liability' => 'Pasivo',
            'equity' => 'Patrimonio',
            'income' => 'Ingresos',
            'expense' => 'Gastos',
            default => 'Sin clasificar',
        };
    }

    public function getNatureLabelAttribute(): string
    {
        return match ($this->nature) {
            'debit' => 'Débito',
            'credit' => 'Crédito',
            default => 'Sin definir',
        };
    }

    public function getFullNameAttribute(): string
    {
        return $this->code.' - '.$this->name;
    }

    public function getHasChildrenAttribute(): bool
    {
        return $this->children()->exists();
    }

    public function getBalanceAttribute(): float
    {
        return $this->getBalance();
    }

    public function getBalance(?string $startDate = null, ?string $endDate = null): float
    {
        $query = $this->transactionEntries()
            ->whereHas('transaction', function ($q) {
                $q->where('status', 'contabilizado');
            });

        if ($startDate) {
            $query->whereHas('transaction', function ($q) use ($startDate) {
                $q->where('transaction_date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('transaction', function ($q) use ($endDate) {
                $q->where('transaction_date', '<=', $endDate);
            });
        }

        $debits = $query->sum('debit_amount');
        $credits = $query->sum('credit_amount');

        return $this->nature === 'debit' ? $debits - $credits : $credits - $debits;
    }

    public function getHierarchicalName(): string
    {
        $names = collect([$this->name]);
        $parent = $this->parent;

        while ($parent) {
            $names->prepend($parent->name);
            $parent = $parent->parent;
        }

        return $names->join(' > ');
    }

    public static function getAccountsByType(int $conjuntoConfigId, string $type): \Illuminate\Database\Eloquent\Collection
    {
        return self::forConjunto($conjuntoConfigId)
            ->byType($type)
            ->active()
            ->orderBy('code')
            ->get();
    }

    public static function buildHierarchicalTree(int $conjuntoConfigId, ?int $parentId = null): array
    {
        $accounts = self::forConjunto($conjuntoConfigId)
            ->where('parent_id', $parentId)
            ->active()
            ->orderBy('code')
            ->get();

        $tree = [];
        foreach ($accounts as $account) {
            $tree[] = [
                'account' => $account,
                'children' => self::buildHierarchicalTree($conjuntoConfigId, $account->id),
            ];
        }

        return $tree;
    }

    public function validateAccountCode(): bool
    {
        $codeLength = strlen($this->code);

        return match ($this->level) {
            1 => $codeLength === 1 && is_numeric($this->code),
            2 => $codeLength === 2 && is_numeric($this->code),
            3 => $codeLength === 4 && is_numeric($this->code),
            4 => $codeLength === 6 && is_numeric($this->code),
            default => false,
        };
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($account) {
            if (! $account->validateAccountCode()) {
                throw new \InvalidArgumentException('Código de cuenta inválido para el nivel especificado');
            }
        });

        static::updating(function ($account) {
            if (! $account->validateAccountCode()) {
                throw new \InvalidArgumentException('Código de cuenta inválido para el nivel especificado');
            }
        });
    }
}
