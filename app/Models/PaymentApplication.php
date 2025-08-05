<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentApplication extends Model
{
    protected $fillable = [
        'payment_id',
        'invoice_id',
        'amount_applied',
        'applied_date',
        'notes',
        'created_by',
        'status',
        'reversed_at',
        'reversed_by',
    ];

    protected $casts = [
        'amount_applied' => 'decimal:2',
        'applied_date' => 'date',
        'reversed_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'status_badge',
        'is_active',
        'is_reversed',
        'can_be_reversed',
    ];

    // Relationships
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'activo');
    }

    public function scopeReversed($query)
    {
        return $query->where('status', 'reversado');
    }

    public function scopeByPayment($query, int $paymentId)
    {
        return $query->where('payment_id', $paymentId);
    }

    public function scopeByInvoice($query, int $invoiceId)
    {
        return $query->where('invoice_id', $invoiceId);
    }

    public function scopeByPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('applied_date', [$startDate, $endDate]);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'activo', 'active' => 'Activo',
            'reversado', 'reversed' => 'Reversado',
            default => 'Sin estado',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'activo', 'active' => ['text' => 'Activo', 'class' => 'bg-green-100 text-green-800'],
            'reversado', 'reversed' => ['text' => 'Reversado', 'class' => 'bg-red-100 text-red-800'],
            default => ['text' => 'Sin estado', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, ['activo', 'active']);
    }

    public function getIsReversedAttribute(): bool
    {
        return in_array($this->status, ['reversado', 'reversed']);
    }

    public function getCanBeReversedAttribute(): bool
    {
        return in_array($this->status, ['activo', 'active']);
    }

    // Methods
    public function reverse(): void
    {
        if (! $this->can_be_reversed) {
            throw new \Exception('Esta aplicación de pago no puede ser reversada');
        }

        \DB::transaction(function () {
            // Update application status
            $this->update([
                'status' => 'reversado',
                'reversed_at' => now(),
                'reversed_by' => auth()->id(),
            ]);

            // Update invoice
            $invoice = $this->invoice;
            $invoice->paid_amount -= $this->amount_applied;
            $invoice->updateStatus();
            $invoice->save();

            // Update payment
            $payment = $this->payment;
            $payment->applied_amount -= $this->amount_applied;
            $payment->updateStatus();

            // Create reversal accounting transaction
            $this->createReversalAccountingTransaction();
        });
    }

    private function createReversalAccountingTransaction(): void
    {
        $invoice = $this->invoice;
        $payment = $this->payment;
        $description = "Reverso aplicación pago {$payment->payment_number} de factura {$invoice->invoice_number}";

        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $payment->conjunto_config_id,
            'transaction_date' => now()->toDateString(),
            'description' => $description,
            'reference_type' => 'payment_application_reversal',
            'reference_id' => $this->id,
            'status' => 'borrador',
            'created_by' => auth()->id(),
        ]);

        // Reverse the original entries
        $transaction->addEntry([
            'account_id' => $this->getAccountByCode('130501'), // CARTERA ADMINISTRACIÓN
            'description' => "Reverso cobro factura - {$description}",
            'debit_amount' => $this->amount_applied,
            'credit_amount' => 0,
            'third_party_type' => 'apartment',
            'third_party_id' => $payment->apartment_id,
        ]);

        $transaction->addEntry([
            'account_id' => $this->getAccountByCode('111001'), // CAJA/BANCO
            'description' => "Reverso pago recibido - {$description}",
            'debit_amount' => 0,
            'credit_amount' => $this->amount_applied,
        ]);

        $transaction->post();
    }

    private function getAccountByCode(string $code): int
    {
        $payment = $this->payment;
        $account = ChartOfAccounts::forConjunto($payment->conjunto_config_id)
            ->where('code', $code)
            ->first();

        if (! $account) {
            throw new \Exception("No se encontró la cuenta contable con código: {$code}");
        }

        return $account->id;
    }
}
