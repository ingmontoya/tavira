<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentAgreementInstallment extends Model
{
    protected $fillable = [
        'payment_agreement_id',
        'installment_number',
        'amount',
        'due_date',
        'status',
        'paid_amount',
        'penalty_amount',
        'paid_date',
        'payment_method',
        'payment_reference',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
        'due_date' => 'date',
        'paid_date' => 'date',
    ];

    protected $appends = [
        'status_badge',
        'status_label',
        'remaining_amount',
        'days_overdue',
        'is_overdue',
    ];

    public function paymentAgreement(): BelongsTo
    {
        return $this->belongsTo(PaymentAgreement::class);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')
            ->orWhere(function ($q) {
                $q->where('status', 'pending')
                    ->where('due_date', '<', now());
            });
    }

    public function markAsPaid(float $amount, ?string $method = null, ?string $reference = null): void
    {
        $this->paid_amount += $amount;
        $this->paid_date = now();

        if ($this->paid_amount >= $this->amount) {
            $this->status = 'paid';
        } else {
            $this->status = 'partial';
        }

        if ($method) {
            $this->payment_method = $method;
        }

        if ($reference) {
            $this->payment_reference = $reference;
        }

        $this->save();
    }

    public function markAsOverdue(): void
    {
        if ($this->status === 'pending' && $this->due_date->isPast()) {
            $this->status = 'overdue';
            $this->calculatePenalty();
            $this->save();
        }
    }

    private function calculatePenalty(): void
    {
        $agreement = $this->paymentAgreement;
        $daysOverdue = $this->due_date->diffInDays(now());

        if ($agreement->penalty_rate > 0 && $daysOverdue > 0) {
            $dailyRate = $agreement->penalty_rate / 100 / 30;
            $this->penalty_amount = $this->amount * $dailyRate * $daysOverdue;
        }
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'paid' => 'Pagado',
            'overdue' => 'Vencido',
            'partial' => 'Pago parcial',
            default => 'Sin estado',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending' => ['text' => 'Pendiente', 'class' => 'bg-yellow-100 text-yellow-800'],
            'paid' => ['text' => 'Pagado', 'class' => 'bg-green-100 text-green-800'],
            'overdue' => ['text' => 'Vencido', 'class' => 'bg-red-100 text-red-800'],
            'partial' => ['text' => 'Pago parcial', 'class' => 'bg-blue-100 text-blue-800'],
            default => ['text' => 'Sin estado', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getRemainingAmountAttribute(): float
    {
        return $this->amount - $this->paid_amount;
    }

    public function getDaysOverdueAttribute(): int
    {
        if ($this->status !== 'overdue' && (! $this->due_date->isPast() || $this->status === 'paid')) {
            return 0;
        }

        return $this->due_date->diffInDays(now());
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status === 'overdue' ||
               ($this->status === 'pending' && $this->due_date->isPast());
    }
}
