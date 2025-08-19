<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'apartment_id',
        'invoice_number',
        'type',
        'billing_date',
        'due_date',
        'billing_period_year',
        'billing_period_month',
        'subtotal',
        'early_discount',
        'late_fees',
        'total_amount',
        'paid_amount',
        'balance_amount',
        'status',
        'last_payment_date',
        'payment_method',
        'payment_reference',
        'notes',
        // Electronic invoicing fields
        'electronic_invoice_status',
        'electronic_invoice_uuid',
        'electronic_invoice_cufe',
        'electronic_invoice_sent_at',
        'electronic_invoice_error',
        'can_be_electronic',
        'factus_id',
        'electronic_invoice_public_url',
        'dian_observation',
        'dian_payment_method',
        'dian_currency',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'due_date' => 'date',
        'last_payment_date' => 'date',
        'subtotal' => 'decimal:2',
        'early_discount' => 'decimal:2',
        'late_fees' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        // Electronic invoicing casts
        'electronic_invoice_sent_at' => 'datetime',
        'can_be_electronic' => 'boolean',
    ];

    protected $appends = [
        'status_badge',
        'status_label',
        'type_label',
        'billing_period_label',
        'days_overdue',
        'balance_due',
    ];

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function paymentApplications(): HasMany
    {
        return $this->hasMany(PaymentApplication::class);
    }

    public function emailDeliveries(): HasMany
    {
        return $this->hasMany(InvoiceEmailDelivery::class);
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('billing_period_year', $year)
            ->where('billing_period_month', $month);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'vencido')
            ->orWhere(function ($q) {
                $q->where('status', 'pendiente')
                    ->where('due_date', '<', now());
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function calculateTotals(): void
    {
        $this->subtotal = $this->items()->sum('total_price');
        $this->total_amount = $this->subtotal - $this->early_discount + $this->late_fees;
        $this->balance_amount = $this->total_amount - $this->paid_amount;
        $this->save();
    }

    public function updateStatus(): void
    {
        // Calculate balance_amount correctly
        $this->balance_amount = $this->total_amount - $this->paid_amount;

        if ($this->paid_amount <= 0) {
            // Check if overdue
            if ($this->due_date < now()->toDateString()) {
                $this->status = 'vencido';
            } else {
                $this->status = 'pendiente';
            }
        } elseif ($this->balance_amount <= 0) {
            // Only mark as paid when balance is zero or negative
            $this->status = 'pagado';
            $this->balance_amount = 0; // Ensure balance doesn't go negative
        } else {
            // There's still a balance, so it's a partial payment
            $this->status = 'pago_parcial';
        }

        $this->save();
    }

    public function markAsPaid(float $amount, ?string $method = null, ?string $reference = null): void
    {
        if ($this->status === 'pendiente') {
            $this->applyEarlyDiscount();
        }

        $this->paid_amount += $amount;
        $this->last_payment_date = now();

        if ($method) {
            $this->payment_method = $method;
        }

        if ($reference) {
            $this->payment_reference = $reference;
        }

        $this->updateStatus();

        // Update apartment payment status after payment
        $this->apartment->updatePaymentStatus();
    }

    public function isOverdue(): bool
    {
        return $this->status === 'vencido' ||
               ($this->status === 'pendiente' && $this->due_date->isPast());
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return $this->due_date->diffInDays(now());
    }

    public function calculateEarlyDiscount(): float
    {
        $paymentSettings = app(\App\Settings\PaymentSettings::class);

        if (! $paymentSettings->early_discount_enabled || $this->status !== 'pendiente') {
            return 0;
        }

        $paymentDate = now();
        $billingDate = $this->billing_date;
        $earlyDiscountDeadline = $billingDate->copy()->addDays($paymentSettings->early_discount_days);

        if ($paymentDate->lte($earlyDiscountDeadline)) {
            return round($this->subtotal * ($paymentSettings->early_discount_percentage / 100), 2);
        }

        return 0;
    }

    public function calculateLateFees(): float
    {
        $paymentSettings = app(\App\Settings\PaymentSettings::class);

        if (! $paymentSettings->late_fees_enabled || ! $this->isOverdue()) {
            return $this->late_fees;
        }

        $gracePeriodEnd = $this->due_date->copy()->addDays($paymentSettings->grace_period_days);

        if (now()->lte($gracePeriodEnd)) {
            return $this->late_fees;
        }

        $monthsOverdue = max(1, $gracePeriodEnd->diffInMonths(now(), false));

        if ($paymentSettings->late_fees_compound_monthly) {
            $compoundRate = 1 + ($paymentSettings->late_fee_percentage / 100);
            $lateFee = $this->subtotal * (pow($compoundRate, $monthsOverdue) - 1);
        } else {
            $lateFee = $this->subtotal * ($paymentSettings->late_fee_percentage / 100) * $monthsOverdue;
        }

        return round($lateFee, 2);
    }

    public function applyEarlyDiscount(): void
    {
        $this->early_discount = $this->calculateEarlyDiscount();
        $this->calculateTotals();
    }

    public function updateLateFees(): void
    {
        $this->late_fees = $this->calculateLateFees();
        $this->calculateTotals();

        if ($this->isOverdue() && $this->status === 'pendiente') {
            $this->status = 'vencido';
            $this->save();
        }
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pendiente', 'pending' => 'Pendiente',
            'pago_parcial', 'partial_payment' => 'Pago Parcial',
            'pagado', 'paid' => 'Pagado',
            'vencido', 'overdue' => 'Vencido',
            'cancelado', 'cancelled' => 'Cancelado',
            default => 'Sin estado',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pendiente', 'pending' => ['text' => 'Pendiente', 'class' => 'bg-yellow-100 text-yellow-800'],
            'pago_parcial', 'partial_payment' => ['text' => 'Pago Parcial', 'class' => 'bg-blue-100 text-blue-800'],
            'pagado', 'paid' => ['text' => 'Pagado', 'class' => 'bg-green-100 text-green-800'],
            'vencido', 'overdue' => ['text' => 'Vencido', 'class' => 'bg-red-100 text-red-800'],
            'cancelado', 'cancelled' => ['text' => 'Cancelado', 'class' => 'bg-gray-100 text-gray-800'],
            default => ['text' => 'Sin estado', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'monthly' => 'Administración mensual',
            'individual' => 'Factura individual',
            'late_fee' => 'Intereses de mora',
            default => 'Sin tipo',
        };
    }

    public function getBillingPeriodLabelAttribute(): string
    {
        $monthNames = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
        ];

        return $monthNames[$this->billing_period_month].' '.$this->billing_period_year;
    }

    public function getBalanceDueAttribute(): float
    {
        return (float) $this->balance_amount;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invoice) {
            if (empty($invoice->invoice_number)) {
                $invoice->invoice_number = self::generateInvoiceNumber($invoice);
            }
        });
    }

    private static function generateInvoiceNumber($invoice): string
    {
        $year = now()->year;
        $month = now()->format('m');

        // Get apartment identifier (tower + apartment number)
        $apartment = Apartment::find($invoice->apartment_id);
        $apartmentIdentifier = $apartment ? $apartment->identifier : '0000';

        $lastInvoice = self::whereYear('created_at', $year)
            ->whereMonth('created_at', now()->month)
            ->where('apartment_id', $invoice->apartment_id)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -2)) + 1 : 1;

        return sprintf('INV-%s%s-%s-%02d', $year, $month, $apartmentIdentifier, $sequence);
    }
}
