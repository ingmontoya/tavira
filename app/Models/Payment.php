<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payment extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'apartment_id',
        'payment_number',
        'total_amount',
        'applied_amount',
        'remaining_amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes',
        'status',
        'created_by',
        'applied_at',
        'applied_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'decimal:2',
        'applied_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'applied_at' => 'datetime',
    ];

    protected $appends = [
        'payment_method_label',
        'status_label',
        'status_badge',
        'is_fully_applied',
        'is_pending',
        'can_be_applied',
    ];

    // Relationships
    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function appliedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applied_by');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(PaymentApplication::class);
    }

    // Scopes
    public function scopeForConjunto($query, int $conjuntoConfigId)
    {
        return $query->where('conjunto_config_id', $conjuntoConfigId);
    }

    public function scopeByApartment($query, int $apartmentId)
    {
        return $query->where('apartment_id', $apartmentId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeApplied($query)
    {
        return $query->whereIn('status', ['aplicado', 'parcialmente_aplicado']);
    }

    public function scopeByPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getPaymentMethodLabelAttribute(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Efectivo',
            'bank_transfer' => 'Transferencia Bancaria',
            'check' => 'Cheque',
            'credit_card' => 'Tarjeta de Crédito',
            'debit_card' => 'Tarjeta Débito',
            'online' => 'Pago Online',
            'other' => 'Otro',
            default => 'No especificado',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pendiente' => 'Pendiente',
            'aplicado' => 'Aplicado',
            'parcialmente_aplicado' => 'Parcialmente Aplicado',
            'reversado' => 'Reversado',
            default => 'Sin estado',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pendiente' => ['text' => 'Pendiente', 'class' => 'bg-yellow-100 text-yellow-800'],
            'aplicado' => ['text' => 'Aplicado', 'class' => 'bg-green-100 text-green-800'],
            'parcialmente_aplicado' => ['text' => 'Parcialmente Aplicado', 'class' => 'bg-blue-100 text-blue-800'],
            'reversado' => ['text' => 'Reversado', 'class' => 'bg-red-100 text-red-800'],
            default => ['text' => 'Sin estado', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getIsFullyAppliedAttribute(): bool
    {
        return $this->status === 'aplicado';
    }

    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pendiente';
    }

    public function getCanBeAppliedAttribute(): bool
    {
        return in_array($this->status, ['pendiente', 'parcialmente_aplicado']) && $this->remaining_amount > 0;
    }

    // Methods
    public function calculateRemainingAmount(): void
    {
        // Ensure proper decimal precision calculation
        $totalAmount = (float) $this->total_amount;
        $appliedAmount = (float) $this->applied_amount;
        $this->remaining_amount = round($totalAmount - $appliedAmount, 2);
        $this->save();
    }

    public function updateStatus(): void
    {
        // Ensure we're working with proper decimal precision
        $appliedAmount = (float) $this->applied_amount;
        $totalAmount = (float) $this->total_amount;

        if ($appliedAmount == 0) {
            $this->status = 'pendiente';
        } elseif ($appliedAmount >= $totalAmount) {
            $this->status = 'aplicado';
        } else {
            $this->status = 'parcialmente_aplicado';
        }

        $this->calculateRemainingAmount();
    }

    public function applyToInvoices(): array
    {
        if (! $this->can_be_applied) {
            throw new \Exception('Este pago no puede ser aplicado');
        }

        // Get pending invoices for this apartment (FIFO)
        $pendingInvoices = Invoice::where('apartment_id', $this->apartment_id)
            ->whereIn('status', ['pending', 'partial_payment', 'overdue'])
            ->orderBy('billing_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $remainingAmount = $this->remaining_amount;
        $applications = [];

        foreach ($pendingInvoices as $invoice) {
            if ($remainingAmount <= 0) {
                break;
            }

            $invoiceBalance = $invoice->total_amount - $invoice->paid_amount;
            if ($invoiceBalance <= 0) {
                continue;
            }

            $amountToApply = min($remainingAmount, $invoiceBalance);

            // Create payment application
            $application = PaymentApplication::create([
                'payment_id' => $this->id,
                'invoice_id' => $invoice->id,
                'amount_applied' => $amountToApply,
                'applied_date' => now(),
                'status' => 'activo',
                'created_by' => auth()->id() ?? $this->created_by,
            ]);

            // Update invoice
            $invoice->paid_amount += $amountToApply;
            $invoice->updateStatus();
            $invoice->save();

            // Update payment
            $this->applied_amount += $amountToApply;
            $remainingAmount -= $amountToApply;

            $applications[] = $application;

            // Create accounting transaction for this application
            $this->createAccountingTransactionForApplication($application);
        }

        // Update payment status
        $this->applied_at = now();
        $this->applied_by = auth()->id() ?? $this->created_by;
        $this->updateStatus();

        return $applications;
    }

    private function createAccountingTransactionForApplication(PaymentApplication $application): void
    {
        $invoice = $application->invoice;
        $description = "Aplicación pago {$this->payment_number} a factura {$invoice->invoice_number}";

        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto_config_id,
            'transaction_date' => $this->payment_date,
            'description' => $description,
            'reference_type' => 'payment_application',
            'reference_id' => $application->id,
            'status' => 'draft',
            'created_by' => auth()->id() ?? $this->created_by,
        ]);

        // If it's a full payment for the invoice, we remove it from accounts receivable
        // If it's partial, we create an internal transfer

        if ($invoice->status === 'paid') {
            // Full payment - remove from accounts receivable
            $transaction->addEntry([
                'account_id' => $this->getAccountByCode('111001'), // CAJA/BANCO
                'description' => "Pago recibido - {$description}",
                'debit_amount' => $application->amount_applied,
                'credit_amount' => 0,
            ]);

            $transaction->addEntry([
                'account_id' => $this->getAccountByCode('130501'), // CARTERA ADMINISTRACIÓN
                'description' => "Cobro factura - {$description}",
                'debit_amount' => 0,
                'credit_amount' => $application->amount_applied,
                'third_party_type' => 'apartment',
                'third_party_id' => $this->apartment_id,
            ]);
        } else {
            // Partial payment - create internal accounting entries for tracking
            $transaction->addEntry([
                'account_id' => $this->getAccountByCode('111001'), // CAJA/BANCO
                'description' => "Pago parcial recibido - {$description}",
                'debit_amount' => $application->amount_applied,
                'credit_amount' => 0,
            ]);

            $transaction->addEntry([
                'account_id' => $this->getAccountByCode('130501'), // CARTERA ADMINISTRACIÓN
                'description' => "Aplicación pago parcial - {$description}",
                'debit_amount' => 0,
                'credit_amount' => $application->amount_applied,
                'third_party_type' => 'apartment',
                'third_party_id' => $this->apartment_id,
            ]);
        }

        $transaction->post();
    }

    private function getAccountByCode(string $code): int
    {
        $account = ChartOfAccounts::forConjunto($this->conjunto_config_id)
            ->where('code', $code)
            ->first();

        if (! $account) {
            throw new \Exception("No se encontró la cuenta contable con código: {$code}");
        }

        return $account->id;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = self::generatePaymentNumber();
            }

            if (empty($payment->remaining_amount)) {
                $payment->remaining_amount = $payment->total_amount;
            }

            if (empty($payment->status)) {
                $payment->status = 'pendiente';
            }

            if (empty($payment->applied_amount)) {
                $payment->applied_amount = 0;
            }
        });
    }

    private static function generatePaymentNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $lastPayment = self::whereYear('created_at', $year)
            ->whereMonth('created_at', now()->month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastPayment ? ((int) substr($lastPayment->payment_number, -4)) + 1 : 1;

        return sprintf('PAY-%s%s-%04d', $year, $month, $sequence);
    }
}
