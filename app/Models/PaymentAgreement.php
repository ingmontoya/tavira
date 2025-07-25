<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentAgreement extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'apartment_id',
        'agreement_number',
        'status',
        'total_debt_amount',
        'initial_payment',
        'monthly_payment',
        'installments',
        'start_date',
        'end_date',
        'penalty_rate',
        'terms_and_conditions',
        'notes',
        'approved_at',
        'approved_by',
        'created_by',
    ];

    protected $casts = [
        'total_debt_amount' => 'decimal:2',
        'initial_payment' => 'decimal:2',
        'monthly_payment' => 'decimal:2',
        'penalty_rate' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
    ];

    protected $appends = [
        'status_badge',
        'status_label',
        'progress_percentage',
        'remaining_balance',
        'overdue_installments_count',
        'next_due_date',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(PaymentAgreementInstallment::class);
    }

    public function installmentItems(): HasMany
    {
        return $this->hasMany(PaymentAgreementInstallment::class);
    }

    public function paidInstallments(): HasMany
    {
        return $this->hasMany(PaymentAgreementInstallment::class)->where('status', 'paid');
    }

    public function overdueInstallments(): HasMany
    {
        return $this->hasMany(PaymentAgreementInstallment::class)->where('status', 'overdue');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeBreached($query)
    {
        return $query->where('status', 'breached');
    }

    public function approve(string $approvedBy): void
    {
        $this->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $approvedBy,
        ]);

        $this->generateInstallments();
    }

    public function activate(): void
    {
        if ($this->status === 'approved') {
            $this->update(['status' => 'active']);
        }
    }

    public function breach(): void
    {
        $this->update(['status' => 'breached']);
    }

    public function complete(): void
    {
        $this->update(['status' => 'completed']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    private function generateInstallments(): void
    {
        $this->installmentItems()->delete();

        $installmentAmount = $this->monthly_payment;
        $currentDate = $this->start_date;

        for ($i = 1; $i <= $this->installments; $i++) {
            PaymentAgreementInstallment::create([
                'payment_agreement_id' => $this->id,
                'installment_number' => $i,
                'amount' => $installmentAmount,
                'due_date' => $currentDate->copy(),
            ]);

            $currentDate->addMonth();
        }
    }

    public function checkCompliance(): void
    {
        $overdueCount = $this->overdueInstallments()->count();

        if ($overdueCount >= 2 && $this->status === 'active') {
            $this->breach();
        } elseif ($this->installmentItems()->where('status', 'paid')->count() === $this->installments) {
            $this->complete();
        }
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Borrador',
            'pending_approval' => 'Pendiente aprobación',
            'approved' => 'Aprobado',
            'active' => 'Activo',
            'breached' => 'Incumplido',
            'completed' => 'Completado',
            'cancelled' => 'Cancelado',
            default => 'Sin estado',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'draft' => ['text' => 'Borrador', 'class' => 'bg-gray-100 text-gray-800'],
            'pending_approval' => ['text' => 'Pendiente aprobación', 'class' => 'bg-yellow-100 text-yellow-800'],
            'approved' => ['text' => 'Aprobado', 'class' => 'bg-blue-100 text-blue-800'],
            'active' => ['text' => 'Activo', 'class' => 'bg-green-100 text-green-800'],
            'breached' => ['text' => 'Incumplido', 'class' => 'bg-red-100 text-red-800'],
            'completed' => ['text' => 'Completado', 'class' => 'bg-emerald-100 text-emerald-800'],
            'cancelled' => ['text' => 'Cancelado', 'class' => 'bg-gray-100 text-gray-800'],
            default => ['text' => 'Sin estado', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->installments === 0) {
            return 0;
        }

        $paidCount = $this->paidInstallments()->count();

        return round(($paidCount / $this->installments) * 100, 2);
    }

    public function getRemainingBalanceAttribute(): float
    {
        $totalPaid = $this->installmentItems()->sum('paid_amount');

        return $this->total_debt_amount - $totalPaid;
    }

    public function getOverdueInstallmentsCountAttribute(): int
    {
        return $this->overdueInstallments()->count();
    }

    public function getNextDueDateAttribute(): ?string
    {
        $nextInstallment = $this->installmentItems()
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date')
            ->first();

        return $nextInstallment?->due_date->format('Y-m-d');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($agreement) {
            if (empty($agreement->agreement_number)) {
                $agreement->agreement_number = self::generateAgreementNumber();
            }
        });
    }

    private static function generateAgreementNumber(): string
    {
        $year = now()->year;
        $lastAgreement = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastAgreement ? ((int) substr($lastAgreement->agreement_number, -4)) + 1 : 1;

        return sprintf('AC-%s-%04d', $year, $sequence);
    }
}
