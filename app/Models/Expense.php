<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conjunto_config_id',
        'expense_category_id',
        'provider_id',
        'expense_number',
        'vendor_name',
        'vendor_document',
        'vendor_email',
        'vendor_phone',
        'description',
        'expense_date',
        'due_date',
        'subtotal',
        'tax_amount',
        'total_amount',
        'status',
        'payment_method',
        'payment_reference',
        'paid_at',
        'notes',
        'debit_account_id',
        'credit_account_id',
        'tax_account_id',
        'created_by',
        'approved_by',
        'approved_at',
        'council_approved_by',
        'council_approved_at',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'approved_at' => 'datetime',
        'council_approved_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
        'status_badge',
        'can_be_approved',
        'can_be_paid',
        'can_be_cancelled',
        'is_overdue',
        'days_overdue',
    ];

    // Relationships
    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function expenseCategory(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function debitAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccounts::class, 'debit_account_id');
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccounts::class, 'credit_account_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function councilApprovedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'council_approved_by');
    }

    public function accountingTransactions(): MorphMany
    {
        return $this->morphMany(AccountingTransaction::class, 'reference');
    }

    public function quotationResponse(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(QuotationResponse::class);
    }

    // Scopes
    public function scopeForConjunto($query, int $conjuntoConfigId)
    {
        return $query->where('conjunto_config_id', $conjuntoConfigId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'aprobado');
    }

    public function scopePaid($query)
    {
        return $query->where('status', 'pagado');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', 'pagado')
            ->where('due_date', '<', now());
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('expense_category_id', $categoryId);
    }

    public function scopeByPeriod($query, string $startDate, string $endDate)
    {
        return $query->whereBetween('expense_date', [$startDate, $endDate]);
    }

    // Attributes
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'borrador' => 'Borrador',
            'pendiente' => 'Pendiente de Aprobación',
            'pendiente_concejo' => 'Pendiente Aprobación Concejo',
            'aprobado' => 'Aprobado',
            'pagado' => 'Pagado',
            'rechazado' => 'Rechazado',
            'cancelado' => 'Cancelado',
            default => 'Sin estado',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'borrador' => ['text' => 'Borrador', 'class' => 'bg-gray-100 text-gray-800'],
            'pendiente' => ['text' => 'Pendiente', 'class' => 'bg-yellow-100 text-yellow-800'],
            'pendiente_concejo' => ['text' => 'Pendiente Concejo', 'class' => 'bg-orange-100 text-orange-800'],
            'aprobado' => ['text' => 'Aprobado', 'class' => 'bg-blue-100 text-blue-800'],
            'pagado' => ['text' => 'Pagado', 'class' => 'bg-green-100 text-green-800'],
            'rechazado' => ['text' => 'Rechazado', 'class' => 'bg-red-100 text-red-800'],
            'cancelado' => ['text' => 'Cancelado', 'class' => 'bg-gray-100 text-gray-800'],
            default => ['text' => 'Sin estado', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getCanBeApprovedAttribute(): bool
    {
        return in_array($this->status, ['borrador', 'pendiente']) &&
               $this->debit_account_id &&
               $this->credit_account_id;
    }

    public function requiresCouncilApproval(): bool
    {
        $settings = app(\App\Settings\ExpenseSettings::class);

        return $settings->council_approval_required &&
               $this->total_amount >= $settings->approval_threshold_amount;
    }

    public function canBypassApproval(): bool
    {
        $settings = app(\App\Settings\ExpenseSettings::class);

        // Check if auto-approval is enabled and amount is below threshold
        if ($settings->auto_approve_below_threshold &&
            $this->total_amount < $settings->approval_threshold_amount) {
            return true;
        }

        // Check if category is in auto-approve list
        if (in_array($this->expense_category_id, $settings->auto_approve_categories)) {
            return true;
        }

        return false;
    }

    public function getCanBePaidAttribute(): bool
    {
        return $this->status === 'aprobado';
    }

    public function getCanBeCancelledAttribute(): bool
    {
        return ! in_array($this->status, ['pagado', 'cancelado']);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->status !== 'pagado' &&
               $this->due_date &&
               $this->due_date->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue) {
            return 0;
        }

        return abs($this->due_date->diffInDays(now()));
    }

    // Methods
    public function approve(int $approvedBy): void
    {
        if (! $this->can_be_approved) {
            throw new \Exception('El gasto no puede ser aprobado en su estado actual');
        }

        // Check if council approval is required
        if ($this->requiresCouncilApproval()) {
            $this->update([
                'status' => 'pendiente_concejo',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            // Send notification to council
            $this->notifyCouncilForApproval();
        } else {
            $this->update([
                'status' => 'aprobado',
                'approved_by' => $approvedBy,
                'approved_at' => now(),
            ]);

            // Create accounting transaction if it doesn't exist
            $this->createAccountingTransaction();

            // Notify creator about approval
            $notificationService = app(\App\Services\ExpenseNotificationService::class);
            $notificationService->notifyApproval($this);
        }
    }

    public function approveByCouncil(int $approvedBy): void
    {
        if ($this->status !== 'pendiente_concejo') {
            throw new \Exception('El gasto debe estar pendiente de aprobación del concejo');
        }

        $this->update([
            'status' => 'aprobado',
            'council_approved_by' => $approvedBy,
            'council_approved_at' => now(),
        ]);

        // Create accounting transaction if it doesn't exist
        $this->createAccountingTransaction();

        // Notify creator about approval
        $notificationService = app(\App\Services\ExpenseNotificationService::class);
        $notificationService->notifyApproval($this);
    }

    private function notifyCouncilForApproval(): void
    {
        $notificationService = app(\App\Services\ExpenseNotificationService::class);
        $notificationService->notifyCouncilApproval($this);
    }

    public function markAsPaid(?string $paymentMethod = null, ?string $paymentReference = null): void
    {
        if (! $this->can_be_paid) {
            throw new \Exception('El gasto no puede ser marcado como pagado');
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($paymentMethod, $paymentReference) {
            $this->update([
                'status' => 'pagado',
                'payment_method' => $paymentMethod ?: 'bank_transfer',
                'payment_reference' => $paymentReference,
                'paid_at' => now(),
            ]);

            // Post the original accounting transaction (provision)
            $this->postAccountingTransaction();

            // Create the payment transaction
            $this->createPaymentTransaction($paymentMethod ?: 'bank_transfer', $paymentReference);
        });
    }

    public function cancel(?string $reason = null): void
    {
        if (! $this->can_be_cancelled) {
            throw new \Exception('El gasto no puede ser cancelado');
        }

        $this->update([
            'status' => 'cancelado',
            'notes' => $this->notes ? $this->notes."\n\nCancelado: ".$reason : 'Cancelado: '.$reason,
        ]);

        // Cancel related accounting transactions
        $this->accountingTransactions()
            ->where('status', 'borrador')
            ->update(['status' => 'cancelado']);
    }

    public function reject(string $reason, int $rejectedBy): void
    {
        $this->update([
            'status' => 'rechazado',
            'notes' => $this->notes ? $this->notes."\n\nRechazado por: ".User::find($rejectedBy)->name.' - '.$reason : 'Rechazado por: '.User::find($rejectedBy)->name.' - '.$reason,
        ]);

        // Notify creator about rejection
        $notificationService = app(\App\Services\ExpenseNotificationService::class);
        $notificationService->notifyRejection($this);
    }

    public function createAccountingTransaction(): void
    {
        if (! $this->debit_account_id || ! $this->credit_account_id) {
            throw new \Exception('Debe especificar las cuentas contables para crear la transacción');
        }

        // Check if accounting transaction already exists
        if ($this->accountingTransactions()->exists()) {
            return; // Don't create duplicate transactions
        }

        // Get vendor name (from supplier or manual entry)
        $vendorName = $this->getVendorDisplayName();

        // Determine transaction status based on expense status
        $transactionStatus = match ($this->status) {
            'aprobado' => 'borrador', // Ready to be posted when paid
            'pagado' => 'contabilizado', // Posted
            default => 'borrador', // Draft for pending expenses
        };

        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto_config_id,
            'transaction_date' => $this->expense_date,
            'description' => "Gasto: {$vendorName} - {$this->description}",
            'reference_type' => 'expense',
            'reference_id' => $this->id,
            'status' => $transactionStatus,
            'created_by' => auth()->id(),
        ]);

        // Débito: Cuenta de gasto
        $transaction->addEntry([
            'account_id' => $this->debit_account_id,
            'description' => "Gasto: {$this->description}",
            'debit_amount' => $this->total_amount,
            'credit_amount' => 0,
        ]);

        // If there's a tax/withholding account and tax amount, create separate entries
        if ($this->tax_account_id && $this->tax_amount > 0) {
            // Crédito: Cuenta de retención en la fuente
            $transaction->addEntry([
                'account_id' => $this->tax_account_id,
                'description' => "Retención en la fuente: {$vendorName}",
                'debit_amount' => 0,
                'credit_amount' => $this->tax_amount,
            ]);

            // Crédito: Cuenta de origen (neto a pagar = total - retención)
            $netAmount = $this->total_amount - $this->tax_amount;
            $creditEntry = [
                'account_id' => $this->credit_account_id,
                'description' => "Pago/Provisión neto: {$vendorName}",
                'debit_amount' => 0,
                'credit_amount' => $netAmount,
            ];
        } else {
            // Crédito: Cuenta de origen (caja/banco o cuentas por pagar) - monto completo
            $creditEntry = [
                'account_id' => $this->credit_account_id,
                'description' => "Pago/Provisión: {$vendorName}",
                'debit_amount' => 0,
                'credit_amount' => $this->total_amount,
            ];
        }

        // If credit account requires third party (like providers account), add provider info
        $creditAccount = ChartOfAccounts::find($this->credit_account_id);
        if ($creditAccount && $creditAccount->requires_third_party && $this->provider_id) {
            $creditEntry['third_party_type'] = 'provider';
            $creditEntry['third_party_id'] = $this->provider_id;
        }

        $transaction->addEntry($creditEntry);
    }

    public function getVendorDisplayName(): string
    {
        if ($this->provider_id && $this->provider) {
            return $this->provider->name;
        }

        return $this->vendor_name ?: 'Proveedor no especificado';
    }

    private function postAccountingTransaction(): void
    {
        $transaction = $this->accountingTransactions()
            ->where('status', 'borrador')
            ->first();

        if ($transaction && $transaction->can_be_posted) {
            $transaction->post();
        }
    }

    private function createPaymentTransaction(string $paymentMethod, ?string $paymentReference = null): void
    {
        // Get vendor name for description
        $vendorName = $this->getVendorDisplayName();

        // Get cash account for the payment method
        $cashAccount = PaymentMethodAccountMapping::getCashAccountForPaymentMethod(
            $this->conjunto_config_id,
            $paymentMethod
        );

        if (! $cashAccount) {
            throw new \Exception("No se encontró mapeo de cuenta para el método de pago: {$paymentMethod}");
        }

        // For expense payments, we need to:
        // 1. Debit the supplier account (reduce liability)
        // 2. Credit the cash/bank account (reduce asset)

        // Get the original credit account (should be the supplier/liability account)
        $supplierAccount = ChartOfAccounts::find($this->credit_account_id);

        if (! $supplierAccount) {
            throw new \Exception('No se encontró la cuenta del proveedor para crear la transacción de pago');
        }

        // Create payment transaction
        $paymentTransaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjunto_config_id,
            'transaction_date' => now()->toDateString(),
            'description' => "Pago de gasto: {$vendorName} - {$this->description}".
                           ($paymentReference ? " (Ref: {$paymentReference})" : ''),
            'reference_type' => 'expense_payment',
            'reference_id' => $this->id,
            'status' => 'borrador',
            'created_by' => auth()->id(),
        ]);

        // Débito: Cuenta del proveedor (reducir pasivo - cuenta por pagar)
        $debitEntry = [
            'account_id' => $supplierAccount->id,
            'description' => "Pago a {$vendorName} - {$this->expense_number}",
            'debit_amount' => $this->total_amount,
            'credit_amount' => 0,
        ];

        // If provider account requires third party, add provider info
        if ($supplierAccount->requires_third_party && $this->provider_id) {
            $debitEntry['third_party_type'] = 'provider';
            $debitEntry['third_party_id'] = $this->provider_id;
        }

        $paymentTransaction->addEntry($debitEntry);

        // Crédito: Cuenta de efectivo/banco (reducir activo)
        $paymentTransaction->addEntry([
            'account_id' => $cashAccount->id,
            'description' => "Pago efectuado - {$this->expense_number}".
                           ($paymentReference ? " (Ref: {$paymentReference})" : ''),
            'debit_amount' => 0,
            'credit_amount' => $this->total_amount,
        ]);

        // Post the payment transaction immediately
        $paymentTransaction->post();
    }

    public static function generateExpenseNumber(int $conjuntoConfigId): string
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($conjuntoConfigId) {
            $year = now()->year;
            $month = now()->format('m');
            $prefix = "EGR-{$year}{$month}-";

            // Start with a random offset to reduce collisions in high-concurrency scenarios
            $randomOffset = rand(0, 10);

            // Get max sequence without FOR UPDATE (PostgreSQL doesn't support FOR UPDATE with aggregates)
            $result = \Illuminate\Support\Facades\DB::select('
                SELECT MAX(CAST(RIGHT(expense_number, 4) AS INTEGER)) as max_sequence
                FROM expenses 
                WHERE conjunto_config_id = ? 
                AND expense_number LIKE ?
            ', [$conjuntoConfigId, "{$prefix}%"]);

            $maxSequence = $result[0]->max_sequence ?? 0;
            $sequence = $maxSequence + 1 + $randomOffset;

            // Try sequences until we find one that doesn't exist
            for ($i = 0; $i < 50; $i++) {
                $expenseNumber = sprintf('%s%04d', $prefix, $sequence + $i);

                // Check if this number is available with a lock
                $exists = \Illuminate\Support\Facades\DB::selectOne('
                    SELECT 1 FROM expenses 
                    WHERE conjunto_config_id = ? 
                    AND expense_number = ? 
                    FOR UPDATE
                ', [$conjuntoConfigId, $expenseNumber]);

                if (! $exists) {
                    return $expenseNumber;
                }
            }

            throw new \Exception('Unable to generate unique expense number after 50 attempts');
        });
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($expense) {
            if (empty($expense->expense_number)) {
                $maxAttempts = 10;
                $attempts = 0;

                while ($attempts < $maxAttempts) {
                    try {
                        $expense->expense_number = self::generateExpenseNumber($expense->conjunto_config_id);
                        break;
                    } catch (\Exception $e) {
                        $attempts++;
                        if ($attempts >= $maxAttempts) {
                            throw new \Exception("Failed to generate unique expense number after {$maxAttempts} attempts: ".$e->getMessage());
                        }
                        // Small random delay to reduce collision probability
                        usleep(rand(1000, 5000)); // 1-5ms delay
                    }
                }
            }
        });
    }
}
