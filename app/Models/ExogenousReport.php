<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExogenousReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conjunto_config_id',
        'report_number',
        'report_type',
        'report_name',
        'fiscal_year',
        'period_start',
        'period_end',
        'status',
        'generated_at',
        'validated_at',
        'exported_at',
        'submitted_at',
        'total_items',
        'total_amount',
        'total_withholding',
        'export_file_path',
        'export_format',
        'notes',
        'metadata',
        'created_by',
        'validated_by',
        'exported_by',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'generated_at' => 'datetime',
        'validated_at' => 'datetime',
        'exported_at' => 'datetime',
        'submitted_at' => 'datetime',
        'total_items' => 'integer',
        'total_amount' => 'decimal:2',
        'total_withholding' => 'decimal:2',
        'metadata' => 'array',
    ];

    protected $appends = [
        'status_label',
        'status_badge',
        'report_type_label',
        'can_be_validated',
        'can_be_exported',
        'can_be_deleted',
    ];

    // Relationships
    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExogenousReportItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function validatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function exportedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'exported_by');
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

    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeByFiscalYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeGenerated($query)
    {
        return $query->whereIn('status', ['generated', 'validated', 'exported', 'submitted']);
    }

    // Attributes
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Borrador',
            'generated' => 'Generado',
            'validated' => 'Validado',
            'exported' => 'Exportado',
            'submitted' => 'Presentado a DIAN',
            default => 'Sin estado',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'draft' => ['text' => 'Borrador', 'class' => 'bg-gray-100 text-gray-800'],
            'generated' => ['text' => 'Generado', 'class' => 'bg-blue-100 text-blue-800'],
            'validated' => ['text' => 'Validado', 'class' => 'bg-green-100 text-green-800'],
            'exported' => ['text' => 'Exportado', 'class' => 'bg-purple-100 text-purple-800'],
            'submitted' => ['text' => 'Presentado', 'class' => 'bg-green-100 text-green-800'],
            default => ['text' => 'Sin estado', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getReportTypeLabelAttribute(): string
    {
        return match ($this->report_type) {
            '1001' => 'Formato 1001 - Pagos y Retenciones',
            '1003' => 'Formato 1003 - Retenciones en la Fuente',
            '1005' => 'Formato 1005 - Ingresos Recibidos',
            '1647' => 'Formato 1647 - Retenciones 1.5%',
            default => 'Formato '.$this->report_type,
        };
    }

    public function getCanBeValidatedAttribute(): bool
    {
        return $this->status === 'generated' && $this->total_items > 0;
    }

    public function getCanBeExportedAttribute(): bool
    {
        return in_array($this->status, ['validated', 'generated']) && $this->total_items > 0;
    }

    public function getCanBeDeletedAttribute(): bool
    {
        return in_array($this->status, ['draft', 'generated']);
    }

    // Methods
    public function calculateTotals(): void
    {
        $this->total_items = $this->items()->count();
        $this->total_amount = $this->items()->sum('payment_amount');
        $this->total_withholding = $this->items()->sum('withholding_amount');
        $this->save();
    }

    public function markAsGenerated(?int $userId = null): void
    {
        $this->update([
            'status' => 'generated',
            'generated_at' => now(),
            'created_by' => $userId ?? auth()->id(),
        ]);
    }

    public function validate(?int $userId = null): void
    {
        if (! $this->can_be_validated) {
            throw new \Exception('El reporte no puede ser validado en su estado actual');
        }

        // Run validation checks
        $validationService = app(\App\Services\ExogenousReportValidationService::class);
        $validation = $validationService->validateReport($this);

        if (! $validation['is_valid']) {
            $errors = implode('; ', $validation['errors']);
            throw new \Exception("El reporte no pasó las validaciones: {$errors}");
        }

        $this->update([
            'status' => 'validated',
            'validated_at' => now(),
            'validated_by' => $userId ?? auth()->id(),
            'metadata' => array_merge($this->metadata ?? [], [
                'validation_results' => $validation,
            ]),
        ]);
    }

    public function markAsExported(string $filePath, string $format, ?int $userId = null): void
    {
        if (! $this->can_be_exported) {
            throw new \Exception('El reporte no puede ser exportado en su estado actual');
        }

        $this->update([
            'status' => 'exported',
            'exported_at' => now(),
            'exported_by' => $userId ?? auth()->id(),
            'export_file_path' => $filePath,
            'export_format' => $format,
        ]);
    }

    public function markAsSubmitted(?int $userId = null): void
    {
        if ($this->status !== 'exported') {
            throw new \Exception('El reporte debe ser exportado antes de marcarlo como presentado');
        }

        $this->update([
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public static function generateReportNumber(int $conjuntoConfigId, string $reportType, int $fiscalYear): string
    {
        $prefix = "REX-{$reportType}-{$fiscalYear}-";

        // Get the maximum sequence number from ALL reports (including soft-deleted)
        // to avoid conflicts with the unique constraint on report_number
        $maxNumber = self::withTrashed()
            ->forConjunto($conjuntoConfigId)
            ->byType($reportType)
            ->byFiscalYear($fiscalYear)
            ->where('report_number', 'LIKE', "{$prefix}%")
            ->get()
            ->map(function ($report) use ($prefix) {
                // Extract the sequence number from report_number (e.g., REX-1003-2025-0001 -> 1)
                return (int) str_replace($prefix, '', $report->report_number);
            })
            ->max();

        $sequence = ($maxNumber ?? 0) + 1;

        return sprintf('%s%04d', $prefix, $sequence);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($report) {
            if (empty($report->report_number)) {
                $report->report_number = self::generateReportNumber(
                    $report->conjunto_config_id,
                    $report->report_type,
                    $report->fiscal_year
                );
            }
        });
    }
}
