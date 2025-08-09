<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class JelpitPaymentImport extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'payment_type',
        'reference_number',
        'transaction_date',
        'transaction_time',
        'transaction_amount',
        'posting_date',
        'approval_number',
        'pse_cycle',
        'office_code',
        'office_name',
        'originator_nit',
        'reference_2',
        'payment_detail',
        'reconciliation_status',
        'apartment_id',
        'match_type',
        'match_notes',
        'payment_id',
        'import_batch_id',
        'processed_by',
        'processed_at',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'posting_date' => 'date',
        'transaction_time' => 'datetime:H:i:s',
        'transaction_amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    protected $appends = [
        'status_badge',
        'cleaned_nit',
        'is_reconciled',
        'can_create_payment',
    ];

    protected $with = ['apartment', 'payment'];

    // Relationships
    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes
    public function scopeForConjunto(Builder $query, int $conjuntoId): Builder
    {
        return $query->where('conjunto_config_id', $conjuntoId);
    }

    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('reconciliation_status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('reconciliation_status', 'pending');
    }

    public function scopeMatched(Builder $query): Builder
    {
        return $query->where('reconciliation_status', 'matched');
    }

    public function scopeByBatch(Builder $query, string $batchId): Builder
    {
        return $query->where('import_batch_id', $batchId);
    }

    // Accessors
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->reconciliation_status) {
            'pending' => ['text' => 'Pendiente', 'class' => 'bg-yellow-100 text-yellow-800'],
            'matched' => ['text' => 'Conciliado', 'class' => 'bg-green-100 text-green-800'],
            'manual_review' => ['text' => 'Revisión Manual', 'class' => 'bg-blue-100 text-blue-800'],
            'rejected' => ['text' => 'Rechazado', 'class' => 'bg-red-100 text-red-800'],
            default => ['text' => 'Sin estado', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getCleanedNitAttribute(): ?string
    {
        if (!$this->originator_nit) {
            return null;
        }
        
        // Remove leading zeros and any non-numeric characters
        return ltrim(preg_replace('/[^0-9]/', '', $this->originator_nit), '0') ?: '0';
    }

    public function getIsReconciledAttribute(): bool
    {
        return $this->reconciliation_status === 'matched' && $this->payment_id !== null;
    }

    public function getCanCreatePaymentAttribute(): bool
    {
        return $this->apartment_id !== null && 
               $this->reconciliation_status === 'matched' && 
               $this->payment_id === null;
    }

    // Methods
    public function attemptAutomaticReconciliation(): bool
    {
        // Strategy 1: Try to match by apartment number (No. Ref)
        if ($this->reference_number && $this->tryMatchByApartmentNumber()) {
            return true;
        }

        // Strategy 2: Try to match by NIT (document number)
        if ($this->originator_nit && $this->tryMatchByNit()) {
            return true;
        }

        // No automatic match found
        $this->reconciliation_status = 'manual_review';
        $this->match_notes = 'No se encontró coincidencia automática. Requiere revisión manual.';
        $this->save();

        return false;
    }

    private function tryMatchByApartmentNumber(): bool
    {
        $apartment = Apartment::forConjunto($this->conjunto_config_id)
            ->where('number', $this->reference_number)
            ->first();

        if ($apartment) {
            $this->apartment_id = $apartment->id;
            $this->match_type = 'apartment_number';
            $this->reconciliation_status = 'matched';
            $this->match_notes = "Coincidencia por número de apartamento: {$apartment->number}";
            $this->save();
            return true;
        }

        return false;
    }

    private function tryMatchByNit(): bool
    {
        $cleanedNit = $this->cleaned_nit;
        
        if (!$cleanedNit || $cleanedNit === '0') {
            return false;
        }

        // Find resident with matching document number
        $resident = Resident::whereHas('apartment', function ($query) {
                $query->where('conjunto_config_id', $this->conjunto_config_id);
            })
            ->where('document_number', $cleanedNit)
            ->where('status', 'Active')
            ->where('resident_type', 'Owner') // Only match owners for payments
            ->first();

        if ($resident && $resident->apartment) {
            $this->apartment_id = $resident->apartment->id;
            $this->match_type = 'nit_match';
            $this->reconciliation_status = 'matched';
            $this->match_notes = "Coincidencia por NIT del propietario: {$resident->full_name} - Apartamento {$resident->apartment->number}";
            $this->save();
            return true;
        }

        return false;
    }

    public function createPayment(): Payment
    {
        if (!$this->can_create_payment) {
            throw new \Exception('No se puede crear el pago. Verificar que esté conciliado y tenga apartamento asignado.');
        }

        $payment = Payment::create([
            'conjunto_config_id' => $this->conjunto_config_id,
            'apartment_id' => $this->apartment_id,
            'total_amount' => $this->transaction_amount,
            'payment_date' => $this->transaction_date,
            'payment_method' => $this->mapPaymentMethod(),
            'reference_number' => $this->approval_number ?? $this->reference_number,
            'notes' => $this->buildPaymentNotes(),
            'status' => 'pendiente',
            'created_by' => auth()->id() ?? $this->created_by,
        ]);

        // Link the import to the created payment BEFORE applying invoices
        $this->payment_id = $payment->id;
        $this->processed_by = auth()->id() ?? $this->created_by;
        $this->processed_at = now();
        $this->save();

        // Automatically apply payment to invoices (FIFO) to generate accounting entries
        $payment->applyToInvoices();

        return $payment;
    }

    private function mapPaymentMethod(): string
    {
        return match (strtolower($this->payment_type)) {
            'pse' => 'online',
            'transferencia de davivienda', 'app davivienda' => 'bank_transfer',
            default => 'other',
        };
    }

    private function buildPaymentNotes(): string
    {
        $notes = ["Pago importado desde Jelpit"];
        
        if ($this->payment_detail) {
            $notes[] = "Detalle: {$this->payment_detail}";
        }
        
        if ($this->approval_number) {
            $notes[] = "Número aprobación: {$this->approval_number}";
        }

        if ($this->match_type) {
            $notes[] = "Tipo de coincidencia: {$this->match_type}";
        }

        return implode(' | ', $notes);
    }

    public static function createFromExcelRow(array $row, int $conjuntoId, string $batchId, int $userId): self
    {
        return self::create([
            'conjunto_config_id' => $conjuntoId,
            'payment_type' => $row['Tipo de pago'] ?? '',
            'reference_number' => $row['No. Ref'] ?? null,
            'transaction_date' => self::parseDate($row['Fecha de transacción']),
            'transaction_time' => self::parseTime($row['Hora de transacción']),
            'transaction_amount' => self::parseAmount($row['Valor transacción']),
            'posting_date' => self::parseDate($row['Fecha de abono']),
            'approval_number' => $row['Número de aprobación'] ?? null,
            'pse_cycle' => $row['Ciclo PSE'] ?? null,
            'office_code' => $row['Código de oficina'] ?? null,
            'office_name' => $row['Nombre de oficina'] ?? null,
            'originator_nit' => $row['NIT Originador'] ?? null,
            'reference_2' => $row['Referencia 2'] ?? null,
            'payment_detail' => $row['Detalle del pago'] ?? null,
            'import_batch_id' => $batchId,
            'created_by' => $userId,
        ]);
    }

    private static function parseDate($dateString): ?\Carbon\Carbon
    {
        if (empty($dateString) || $dateString === 'NaN') {
            return null;
        }

        try {
            return \Carbon\Carbon::createFromFormat('d/m/Y', $dateString);
        } catch (\Exception) {
            return null;
        }
    }

    private static function parseTime($timeString): ?\Carbon\Carbon
    {
        if (empty($timeString) || $timeString === 'NaN') {
            return null;
        }

        try {
            return \Carbon\Carbon::createFromFormat('H:i:s', $timeString);
        } catch (\Exception) {
            return null;
        }
    }

    private static function parseAmount($amountString): float
    {
        if (empty($amountString) || $amountString === 'NaN') {
            return 0.00;
        }

        // Remove any non-numeric characters except decimal point
        $cleaned = preg_replace('/[^0-9.]/', '', $amountString);
        return (float) $cleaned;
    }
}
