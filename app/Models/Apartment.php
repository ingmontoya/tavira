<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'apartment_type_id',
        'number',
        'tower',
        'floor',
        'position_on_floor',
        'status',
        'monthly_fee',
        'utilities',
        'features',
        'notes',
        'last_payment_date',
        'outstanding_balance',
        'payment_status',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'utilities' => 'array',
        'features' => 'array',
        'last_payment_date' => 'date',
        'outstanding_balance' => 'decimal:2',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function apartmentType(): BelongsTo
    {
        return $this->belongsTo(ApartmentType::class);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function paymentAgreements(): HasMany
    {
        return $this->hasMany(PaymentAgreement::class);
    }

    public function getFullAddressAttribute(): string
    {
        return $this->tower ? "Torre {$this->tower} - Apt {$this->number}" : "Apt {$this->number}";
    }

    public function getIsOccupiedAttribute(): bool
    {
        return $this->status === 'Occupied';
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'Available';
    }

    public function scopeByTower($query, string $tower)
    {
        return $query->where('tower', $tower);
    }

    public function scopeByFloor($query, int $floor)
    {
        return $query->where('floor', $floor);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, int $apartmentTypeId)
    {
        return $query->where('apartment_type_id', $apartmentTypeId);
    }

    public function updatePaymentStatus(): void
    {
        $lastMonth = now()->subMonth()->endOfMonth();

        if (! $this->last_payment_date || $this->last_payment_date->lt($lastMonth)) {
            $daysSinceLastPayment = $this->last_payment_date
                ? $this->last_payment_date->diffInDays($lastMonth)
                : 999;

            if ($daysSinceLastPayment >= 90) {
                $this->payment_status = 'overdue_90_plus';
            } elseif ($daysSinceLastPayment >= 60) {
                $this->payment_status = 'overdue_90';
            } elseif ($daysSinceLastPayment >= 30) {
                $this->payment_status = 'overdue_60';
            } else {
                $this->payment_status = 'overdue_30';
            }
        } else {
            $this->payment_status = 'current';
        }

        $this->save();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->last_payment_date) {
            return 999;
        }

        $lastMonth = now()->subMonth()->endOfMonth();

        return $this->last_payment_date->lt($lastMonth)
            ? $this->last_payment_date->diffInDays($lastMonth)
            : 0;
    }

    public function getPaymentStatusBadgeAttribute(): array
    {
        return match ($this->payment_status) {
            'current' => ['text' => 'Al día', 'class' => 'bg-green-100 text-green-800'],
            'overdue_30' => ['text' => '30 días', 'class' => 'bg-yellow-100 text-yellow-800'],
            'overdue_60' => ['text' => '60 días', 'class' => 'bg-orange-100 text-orange-800'],
            'overdue_90' => ['text' => '90 días', 'class' => 'bg-red-100 text-red-800'],
            'overdue_90_plus' => ['text' => '+90 días', 'class' => 'bg-red-200 text-red-900'],
            default => ['text' => 'Sin datos', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getIsDelinquentAttribute(): bool
    {
        return in_array($this->payment_status, ['overdue_30', 'overdue_60', 'overdue_90', 'overdue_90_plus']);
    }

    public function scopeDelinquent($query)
    {
        return $query->whereIn('payment_status', ['overdue_30', 'overdue_60', 'overdue_90', 'overdue_90_plus']);
    }

    public function scopeByPaymentStatus($query, string $status)
    {
        return $query->where('payment_status', $status);
    }
}
