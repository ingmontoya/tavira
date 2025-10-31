<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Expense Model
 *
 * Handles expense tracking and accounting for the conjunto.
 *
 * IMPORTANT - Amount Fields:
 * - subtotal: The total value of the service/purchase (NOT affected by withholdings)
 * - tax_amount: Withholding tax amount (retención en la fuente) - INFORMATIONAL ONLY
 *   This amount is deducted from the net payment but DOES NOT change the expense total
 * - total_amount: Same as subtotal (the full expense amount)
 *
 * Example:
 * - Service cost: $1,000,000 (subtotal and total_amount)
 * - Withholding tax: $100,000 (tax_amount - cuenta 2365)
 * - Net payment: $900,000 (what's actually paid)
 *
 * Accounting Entry:
 * - Debit: Expense account (e.g., 513510 Vigilancia) $1,000,000
 * - Credit: Withholding account (2365xx) $100,000
 * - Credit: Bank/Payable account $900,000
 */
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
        'tax_amount',  // Retención en la fuente (withholding tax)
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
        \Illuminate\Support\Facades\DB::transaction(function () use ($paymentMethod, $paymentReference) {
            // Lock the row for update to prevent concurrent modifications
            $expense = self::lockForUpdate()->find($this->id);

            if (! $expense) {
                throw new \Exception('El gasto no fue encontrado');
            }

            // Re-check status after acquiring lock
            if ($expense->status === 'pagado') {
                throw new \Exception('El gasto ya está marcado como pagado');
            }

            if ($expense->status !== 'aprobado') {
                throw new \Exception('El gasto no puede ser marcado como pagado. Estado actual: '.$expense->status);
            }

            $expense->update([
                'status' => 'pagado',
                'payment_method' => $paymentMethod ?: 'bank_transfer',
                'payment_reference' => $paymentReference,
                'paid_at' => now(),
            ]);

            // Ensure provider exists for third party accounting requirements
            $expense->ensureProviderForThirdPartyAccounts();

            // Post the original accounting transaction (provision)
            $expense->postAccountingTransaction();

            // Create the payment transaction
            $expense->createPaymentTransaction($paymentMethod ?: 'bank_transfer', $paymentReference);

            // Refresh the current model instance
            $this->refresh();
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

        // Ensure we have a provider_id if any account requires third party
        $this->ensureProviderForThirdPartyAccounts();

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

        // Débito: Cuenta de gasto por el valor total del servicio/compra
        // Ejemplo: 513510 Vigilancia $1,000,000
        $transaction->addEntry([
            'account_id' => $this->debit_account_id,
            'description' => "Gasto: {$this->description}",
            'debit_amount' => $this->total_amount,
            'credit_amount' => 0,
        ]);

        // If there's withholding tax (retención en la fuente), create separate entries
        // The withholding reduces the net payment but doesn't change the expense amount
        if ($this->tax_account_id && $this->tax_amount > 0) {
            // Crédito: Cuenta de retención en la fuente (2365)
            // Ejemplo: 236525 Retención Servicios $100,000
            // The withholding account requires third party info (who we're withholding from)
            $taxEntry = [
                'account_id' => $this->tax_account_id,
                'description' => "Retención en la fuente: {$vendorName}",
                'debit_amount' => 0,
                'credit_amount' => $this->tax_amount,
            ];

            // Check if tax account requires third party and add provider info
            $taxAccount = ChartOfAccounts::find($this->tax_account_id);
            if ($taxAccount && $taxAccount->requires_third_party && $this->provider_id) {
                $taxEntry['third_party_type'] = 'provider';
                $taxEntry['third_party_id'] = $this->provider_id;
            }

            $transaction->addEntry($taxEntry);

            // Crédito: Cuenta de origen (neto a pagar = total - retención)
            // Ejemplo: 111005 Bancos $900,000 (si pago inmediato)
            // Ejemplo: 2335 Cuentas por pagar $900,000 (si es a crédito)
            $netAmount = $this->total_amount - $this->tax_amount;
            $creditEntry = [
                'account_id' => $this->credit_account_id,
                'description' => "Pago/Provisión neto: {$vendorName}",
                'debit_amount' => 0,
                'credit_amount' => $netAmount,
            ];
        } else {
            // Crédito: Cuenta de origen (caja/banco o cuentas por pagar) - monto completo
            // Sin retención, se paga el monto completo
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

    /**
     * Ensure the expense has a provider_id if any of the accounting accounts require third party
     * If vendor_name is set but no provider_id, create a provider automatically
     */
    private function ensureProviderForThirdPartyAccounts(): void
    {
        // If we already have a provider_id, nothing to do
        if ($this->provider_id) {
            return;
        }

        // If we don't even have a vendor_name, nothing we can do
        if (! $this->vendor_name) {
            return;
        }

        // Check if any of the accounts require third party
        $requiresThirdParty = false;

        if ($this->credit_account_id) {
            $creditAccount = ChartOfAccounts::find($this->credit_account_id);
            if ($creditAccount && $creditAccount->requires_third_party) {
                $requiresThirdParty = true;
            }
        }

        if (!$requiresThirdParty && $this->tax_account_id) {
            $taxAccount = ChartOfAccounts::find($this->tax_account_id);
            if ($taxAccount && $taxAccount->requires_third_party) {
                $requiresThirdParty = true;
            }
        }

        // If no account requires third party, nothing to do
        if (! $requiresThirdParty) {
            return;
        }

        // At this point, we have vendor_name but no provider_id, and accounts require third party
        // Create a provider automatically
        $provider = \App\Models\Provider::firstOrCreate(
            [
                'name' => $this->vendor_name,
            ],
            [
                'document' => $this->vendor_document,
                'email' => $this->vendor_email,
                'phone' => $this->vendor_phone,
                'is_active' => true,
            ]
        );

        // Update the expense with the provider_id
        $this->update(['provider_id' => $provider->id]);

        // Update existing accounting transaction entries to include third party
        $this->updateExistingTransactionEntriesWithThirdParty($provider->id);
    }

    /**
     * Update existing accounting transaction entries to include third party information
     */
    private function updateExistingTransactionEntriesWithThirdParty(int $providerId): void
    {
        // Find existing provision transaction
        $transaction = $this->accountingTransactions()
            ->where('reference_type', 'expense')
            ->where('status', 'borrador')
            ->first();

        if (! $transaction) {
            return;
        }

        // Update credit account entry if it requires third party
        if ($this->credit_account_id) {
            $creditAccount = ChartOfAccounts::find($this->credit_account_id);
            if ($creditAccount && $creditAccount->requires_third_party) {
                $transaction->entries()
                    ->where('account_id', $this->credit_account_id)
                    ->update([
                        'third_party_type' => 'provider',
                        'third_party_id' => $providerId,
                    ]);
            }
        }

        // Update tax account entry if it requires third party
        if ($this->tax_account_id) {
            $taxAccount = ChartOfAccounts::find($this->tax_account_id);
            if ($taxAccount && $taxAccount->requires_third_party) {
                $transaction->entries()
                    ->where('account_id', $this->tax_account_id)
                    ->update([
                        'third_party_type' => 'provider',
                        'third_party_id' => $providerId,
                    ]);
            }
        }
    }

    private function createPaymentTransaction(string $paymentMethod, ?string $paymentReference = null): void
    {
        // Check if payment transaction already exists
        $existingPaymentTransaction = $this->accountingTransactions()
            ->where('reference_type', 'expense_payment')
            ->where('reference_id', $this->id)
            ->first();

        if ($existingPaymentTransaction) {
            // Payment transaction already exists, just post it if needed
            if ($existingPaymentTransaction->status === 'borrador' && $existingPaymentTransaction->can_be_posted) {
                $existingPaymentTransaction->post();
            }

            return;
        }

        // Get vendor name for description
        $vendorName = $this->getVendorDisplayName();

        // Get the original credit account from the provision transaction
        $creditAccount = ChartOfAccounts::find($this->credit_account_id);

        if (! $creditAccount) {
            throw new \Exception('No se encontró la cuenta de crédito para crear la transacción de pago');
        }

        // IMPORTANT: Check if the credit account is a cash/bank account (1110xx)
        // If it is, it means this expense was already paid in the provision transaction
        // and we should NOT create a separate payment transaction
        if (str_starts_with($creditAccount->code, '1110') || str_starts_with($creditAccount->code, '1105')) {
            // This is a cash/bank account - expense was paid immediately in provision
            // No need to create a payment transaction
            return;
        }

        // If we get here, the credit account is a liability account (e.g., 2335 - Cuentas por Pagar)
        // This means the expense was provisioned (a crédito) and now needs to be paid

        // Get cash account for the payment method
        $cashAccount = PaymentMethodAccountMapping::getCashAccountForPaymentMethod(
            $this->conjunto_config_id,
            $paymentMethod
        );

        if (! $cashAccount) {
            // Try to get a default cash/bank account as fallback
            $cashAccount = ChartOfAccounts::where('conjunto_config_id', $this->conjunto_config_id)
                ->where('code', 'LIKE', '1110%') // Bancos
                ->where('is_active', true)
                ->where('is_postable', true)
                ->first();

            if (! $cashAccount) {
                throw new \Exception("No se encontró mapeo de cuenta para el método de pago: {$paymentMethod}. Por favor configure los mapeos de métodos de pago en Configuración > Mapeo de Métodos de Pago.");
            }
        }

        // Calculate the net payment amount (considering withholding tax)
        // If there's withholding tax, the net amount paid is: total_amount - tax_amount
        // Otherwise, the full total_amount is paid
        $netPaymentAmount = $this->tax_amount > 0
            ? $this->total_amount - $this->tax_amount
            : $this->total_amount;

        // For expense payments of provisioned expenses, we need to:
        // 1. Debit the liability account (2335 - reduce accounts payable)
        // 2. Credit the cash/bank account (reduce asset)

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

        // Débito: Cuenta del pasivo (2335 - Costos y Gastos por Pagar)
        // This reduces the liability that was created in the provision transaction
        $debitEntry = [
            'account_id' => $creditAccount->id,
            'description' => "Pago a {$vendorName} - {$this->expense_number}",
            'debit_amount' => $netPaymentAmount,
            'credit_amount' => 0,
        ];

        // If liability account requires third party, add provider info
        if ($creditAccount->requires_third_party && $this->provider_id) {
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
            'credit_amount' => $netPaymentAmount,
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
