<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExogenousReportItem extends Model
{
    protected $fillable = [
        'exogenous_report_id',
        'provider_id',
        'third_party_document_type',
        'third_party_document_number',
        'third_party_verification_digit',
        'third_party_name',
        'third_party_address',
        'third_party_city',
        'third_party_country',
        'concept_code',
        'concept_name',
        'payment_amount',
        'withholding_amount',
        'tax_base',
        'withholding_rate',
        'source_type',
        'source_id',
        'transaction_number',
        'transaction_date',
        'account_code',
        'account_name',
        'metadata',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'withholding_amount' => 'decimal:2',
        'tax_base' => 'decimal:2',
        'withholding_rate' => 'decimal:2',
        'transaction_date' => 'date',
        'metadata' => 'array',
    ];

    protected $appends = [
        'net_payment',
        'formatted_document',
    ];

    // Relationships
    public function exogenousReport(): BelongsTo
    {
        return $this->belongsTo(ExogenousReport::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    // Scopes
    public function scopeByReport($query, int $reportId)
    {
        return $query->where('exogenous_report_id', $reportId);
    }

    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeByDocument($query, string $documentNumber)
    {
        return $query->where('third_party_document_number', $documentNumber);
    }

    public function scopeWithWithholding($query)
    {
        return $query->where('withholding_amount', '>', 0);
    }

    public function scopeAboveThreshold($query, float $threshold)
    {
        return $query->where('payment_amount', '>=', $threshold);
    }

    // Attributes
    public function getNetPaymentAttribute(): float
    {
        return $this->payment_amount - $this->withholding_amount;
    }

    public function getFormattedDocumentAttribute(): string
    {
        $doc = $this->third_party_document_number;

        if ($this->third_party_verification_digit) {
            return "{$doc}-{$this->third_party_verification_digit}";
        }

        return $doc;
    }

    // Methods
    public static function createFromExpense(ExogenousReport $report, Expense $expense): self
    {
        $provider = $expense->provider;

        if (! $provider) {
            throw new \Exception('El gasto debe tener un proveedor asignado para incluirlo en el reporte exógeno');
        }

        // Calculate withholding if not already set
        $withholdingAmount = $expense->tax_amount ?? 0;
        $paymentAmount = $expense->total_amount;
        $taxBase = $expense->subtotal;

        // Calculate withholding rate if we have withholding
        $withholdingRate = null;
        if ($withholdingAmount > 0 && $taxBase > 0) {
            $withholdingRate = ($withholdingAmount / $taxBase) * 100;
        }

        // Extract verification digit from document number if present
        $documentParts = explode('-', $provider->document_number);
        $documentNumber = $documentParts[0];
        $verificationDigit = $documentParts[1] ?? null;

        // Get account information
        $debitAccount = $expense->debitAccount;

        return self::create([
            'exogenous_report_id' => $report->id,
            'provider_id' => $provider->id,
            'third_party_document_type' => $provider->document_type ?? 'NIT',
            'third_party_document_number' => $documentNumber,
            'third_party_verification_digit' => $verificationDigit,
            'third_party_name' => $provider->name,
            'third_party_address' => $provider->address,
            'third_party_city' => $provider->city ?? 'Bogotá',
            'third_party_country' => $provider->country ?? 'Colombia',
            'concept_code' => self::getConceptCodeForExpense($expense),
            'concept_name' => $expense->description,
            'payment_amount' => $paymentAmount,
            'withholding_amount' => $withholdingAmount,
            'tax_base' => $taxBase,
            'withholding_rate' => $withholdingRate,
            'source_type' => 'expense',
            'source_id' => $expense->id,
            'transaction_number' => $expense->expense_number,
            'transaction_date' => $expense->expense_date,
            'account_code' => $debitAccount?->code,
            'account_name' => $debitAccount?->name,
            'metadata' => [
                'expense_category_id' => $expense->expense_category_id,
                'payment_method' => $expense->payment_method,
            ],
        ]);
    }

    private static function getConceptCodeForExpense(Expense $expense): ?string
    {
        // Map expense categories/accounts to DIAN concept codes
        // This is a simplified version - should be configurable
        $accountCode = $expense->debitAccount?->code;

        return match (true) {
            str_starts_with($accountCode, '5135') => '28', // Servicios varios
            str_starts_with($accountCode, '5140') => '29', // Honorarios
            str_starts_with($accountCode, '5145') => '30', // Arrendamientos
            str_starts_with($accountCode, '5195') => '31', // Gastos diversos
            default => '28', // Default to servicios varios
        };
    }

    public function validate(): array
    {
        $errors = [];

        // Validate required fields
        if (empty($this->third_party_document_number)) {
            $errors[] = 'Número de documento del tercero es requerido';
        }

        if (empty($this->third_party_name)) {
            $errors[] = 'Nombre del tercero es requerido';
        }

        if ($this->payment_amount <= 0) {
            $errors[] = 'El monto del pago debe ser mayor a cero';
        }

        // Validate document format (NIT should be numeric)
        if ($this->third_party_document_type === 'NIT' && ! is_numeric($this->third_party_document_number)) {
            $errors[] = 'El NIT debe ser numérico';
        }

        // Validate withholding amount doesn't exceed payment
        if ($this->withholding_amount > $this->payment_amount) {
            $errors[] = 'El monto de retención no puede ser mayor al pago';
        }

        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
        ];
    }
}
