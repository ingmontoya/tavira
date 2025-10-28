<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AccountingTransaction extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'transaction_number',
        'transaction_date',
        'description',
        'reference_type',
        'reference_id',
        'total_debit',
        'total_credit',
        'status',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $attributes = [
        'total_debit' => 0,
        'total_credit' => 0,
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'total_debit' => 'decimal:2',
        'total_credit' => 'decimal:2',
        'posted_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'status_badge',
        'is_balanced',
        'can_be_posted',
        'can_be_cancelled',
        'total_amount',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(AccountingTransactionEntry::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reference(): MorphTo
    {
        return $this->morphTo('reference', 'reference_type', 'reference_id');
    }

    /**
     * Get the reference model, returns null for manual transactions
     */
    public function getReferencedModel()
    {
        if ($this->reference_type === 'manual' || !$this->reference_id) {
            return null;
        }

        return $this->reference;
    }

    public function apartment(): ?BelongsTo
    {
        // Get apartment through the referenced object (skip for manual transactions)
        $reference = $this->getReferencedModel();

        if (! $reference) {
            return null;
        }

        // Handle different reference types
        if ($this->reference_type === 'invoice' && method_exists($reference, 'apartment')) {
            return $reference->apartment();
        }

        if ($this->reference_type === 'payment' && method_exists($reference, 'apartment')) {
            return $reference->apartment();
        }

        if ($this->reference_type === 'payment_application' && method_exists($reference, 'invoice')) {
            return $reference->invoice->apartment();
        }

        if ($this->reference_type === 'payment_application_reversal' && method_exists($reference, 'invoice')) {
            return $reference->invoice->apartment();
        }

        // Expenses don't have a direct apartment relationship
        if ($this->reference_type === 'expense') {
            return null;
        }

        return null;
    }

    public function getApartmentAttribute()
    {
        // Get apartment through the referenced object (skip for manual transactions)
        $reference = $this->getReferencedModel();

        if (! $reference) {
            return null;
        }

        // Handle different reference types
        if ($this->reference_type === 'invoice' && $reference->apartment) {
            return $reference->apartment;
        }

        if ($this->reference_type === 'payment' && $reference->apartment) {
            return $reference->apartment;
        }

        if ($this->reference_type === 'payment_application' && $reference->invoice && $reference->invoice->apartment) {
            return $reference->invoice->apartment;
        }

        if ($this->reference_type === 'payment_application_reversal' && $reference->invoice && $reference->invoice->apartment) {
            return $reference->invoice->apartment;
        }

        // Expenses don't have a direct apartment relationship
        if ($this->reference_type === 'expense') {
            return null;
        }

        return null;
    }

    public function scopeForConjunto($query, int $conjuntoConfigId)
    {
        return $query->where('conjunto_config_id', $conjuntoConfigId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'contabilizado');
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'borrador');
    }

    public function scopeByPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    public function scopeByReference($query, string $referenceType, int $referenceId)
    {
        return $query->where('reference_type', $referenceType)
            ->where('reference_id', $referenceId);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'Borrador',
            'contabilizado' => 'Contabilizado',
            'cancelado' => 'Cancelado',
            default => 'Sin estado',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'borrador' => ['text' => 'Borrador', 'class' => 'bg-yellow-100 text-yellow-800'],
            'contabilizado' => ['text' => 'Contabilizado', 'class' => 'bg-green-100 text-green-800'],
            'cancelado' => ['text' => 'Cancelado', 'class' => 'bg-red-100 text-red-800'],
            default => ['text' => 'Sin estado', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getIsBalancedAttribute(): bool
    {
        return $this->total_debit == $this->total_credit;
    }

    public function getCanBePostedAttribute(): bool
    {
        return $this->status === 'borrador' &&
               $this->is_balanced &&
               $this->entries()->count() > 0;
    }

    public function getCanBeCancelledAttribute(): bool
    {
        return $this->status === 'contabilizado';
    }

    public function getTotalAmountAttribute(): float
    {
        // En contabilidad de doble entrada, débito y crédito deben ser iguales
        // Retornamos el total de débito (que debería ser igual al crédito)
        return (float) ($this->total_debit ?? 0);
    }

    public function calculateTotals(): void
    {
        $this->total_debit = $this->entries()->sum('debit_amount');
        $this->total_credit = $this->entries()->sum('credit_amount');
        $this->save();
    }

    public function validateDoubleEntry(): bool
    {
        $this->calculateTotals();

        return $this->is_balanced && $this->total_debit > 0;
    }

    public function post(bool $skipPeriodValidation = false): void
    {
        if (! $this->can_be_posted) {
            throw new \Exception('La transacción no puede ser contabilizada');
        }

        if (! $this->validateDoubleEntry()) {
            throw new \Exception('La transacción no cumple con la partida doble');
        }

        // Ejecutar validaciones adicionales de integridad
        $validationService = new \App\Services\AccountingValidationService;
        $validation = $validationService->validateTransactionIntegrity($this, $skipPeriodValidation);

        if (! $validation['is_valid']) {
            $errors = implode('; ', $validation['errors']);
            throw new \Exception("La transacción no pasa las validaciones de integridad: {$errors}");
        }

        // Log warnings if any
        if (! empty($validation['warnings'])) {
            \Illuminate\Support\Facades\Log::warning('Transacción contabilizada con advertencias', [
                'transaction_id' => $this->id,
                'transaction_number' => $this->transaction_number,
                'warnings' => $validation['warnings'],
            ]);
        }

        $this->update([
            'status' => 'contabilizado',
            'posted_by' => auth()->id(),
            'posted_at' => now(),
        ]);

        // Fire event to update budget executions
        event(new \App\Events\AccountingTransactionPosted($this));
    }

    public function cancel(): void
    {
        if (! $this->can_be_cancelled) {
            throw new \Exception('La transacción no puede ser cancelada');
        }

        $this->update(['status' => 'cancelado']);
    }

    public function addEntry(array $entryData): AccountingTransactionEntry
    {
        if ($this->status !== 'borrador') {
            throw new \Exception('Solo se pueden agregar movimientos a transacciones en borrador');
        }

        $entry = $this->entries()->create($entryData);
        $this->calculateTotals();

        return $entry;
    }

    public static function createFromInvoice(Invoice $invoice): self
    {
        $invoice->load(['apartment.apartmentType', 'items.paymentConcept']);

        $transaction = self::create([
            'conjunto_config_id' => $invoice->apartment->apartmentType->conjunto_config_id,
            'transaction_date' => $invoice->billing_date,
            'description' => "Apto {$invoice->apartment->number} - Factura {$invoice->invoice_number}",
            'reference_type' => 'invoice',
            'reference_id' => $invoice->id,
            'created_by' => auth()->id(),
        ]);

        // Procesar cada item de la factura usando su mapeo contable
        foreach ($invoice->items as $item) {
            $mapping = PaymentConceptAccountMapping::getAccountsForConcept($item->payment_concept_id);

            if (! $mapping || ! $mapping['income_account'] || ! $mapping['receivable_account']) {
                throw new \Exception("No se encontró mapeo contable válido para el concepto: {$item->paymentConcept->name}");
            }

            $itemTotal = $item->quantity * $item->unit_price;

            // Débito: Cuenta por cobrar específica del concepto
            $transaction->addEntry([
                'account_id' => $mapping['receivable_account']->id,
                'description' => "Cartera - {$item->description}",
                'debit_amount' => $itemTotal,
                'credit_amount' => 0,
                'third_party_type' => 'apartment',
                'third_party_id' => $invoice->apartment_id,
            ]);

            // Crédito: Cuenta de ingresos específica del concepto
            $transaction->addEntry([
                'account_id' => $mapping['income_account']->id,
                'description' => "Ingreso - {$item->description}",
                'debit_amount' => 0,
                'credit_amount' => $itemTotal,
            ]);
        }

        $transaction->post();

        return $transaction;
    }

    public function createFromPayment(Invoice $invoice, float $paymentAmount): self
    {
        $transaction = self::create([
            'conjunto_config_id' => $invoice->apartment->apartmentType->conjunto_config_id,
            'transaction_date' => now()->toDateString(),
            'description' => "Apto {$invoice->apartment->number} - Pago factura {$invoice->invoice_number}",
            'reference_type' => 'payment',
            'reference_id' => $invoice->id,
            'created_by' => auth()->id(),
        ]);

        // Débito: Caja/Banco
        $transaction->addEntry([
            'account_id' => $this->getAccountByCode($invoice->apartment->apartmentType->conjunto_config_id, '112005'),
            'description' => "Pago recibido factura {$invoice->invoice_number}",
            'debit_amount' => $paymentAmount,
            'credit_amount' => 0,
        ]);

        // Crédito: Cartera de clientes
        $transaction->addEntry([
            'account_id' => $this->getAccountByCode($invoice->apartment->apartmentType->conjunto_config_id, '13050505'),
            'description' => "Pago cartera factura {$invoice->invoice_number}",
            'debit_amount' => 0,
            'credit_amount' => $paymentAmount,
            'third_party_type' => 'apartment',
            'third_party_id' => $invoice->apartment_id,
        ]);

        $transaction->post();

        return $transaction;
    }

    private function getAccountByCode(int $conjuntoConfigId, string $code): int
    {
        $account = ChartOfAccounts::forConjunto($conjuntoConfigId)
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

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = self::generateTransactionNumber($transaction);
            }
        });
    }

    private static function generateTransactionNumber($transaction = null): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $lastTransaction = self::whereYear('created_at', $year)
            ->whereMonth('created_at', now()->month)
            ->orderBy('transaction_number', 'desc')
            ->first();

        $sequence = $lastTransaction ? ((int) substr($lastTransaction->transaction_number, -4)) + 1 : 1;

        // Try to get apartment number from reference
        $apartmentCode = '';
        if ($transaction && $transaction->reference_type === 'invoice' && $transaction->reference_id) {
            $invoice = \App\Models\Invoice::find($transaction->reference_id);
            if ($invoice && $invoice->apartment) {
                $apartmentCode = $invoice->apartment->number.'-';
            }
        } elseif ($transaction && $transaction->reference_type === 'payment' && $transaction->reference_id) {
            $invoice = \App\Models\Invoice::find($transaction->reference_id);
            if ($invoice && $invoice->apartment) {
                $apartmentCode = $invoice->apartment->number.'-';
            }
        } elseif ($transaction && $transaction->reference_type === 'expense' && $transaction->reference_id) {
            // For expenses, use EXP- prefix instead of apartment code
            $expense = \App\Models\Expense::find($transaction->reference_id);
            if ($expense) {
                $apartmentCode = 'EXP-';
            }
        }

        return sprintf('TXN-%s%s%s-%04d', $apartmentCode, $year, $month, $sequence);
    }
}
