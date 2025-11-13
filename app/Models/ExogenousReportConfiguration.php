<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExogenousReportConfiguration extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'fiscal_year',
        'report_type',
        'minimum_reporting_amount',
        'include_amounts_below_threshold',
        'withholding_rates',
        'concept_codes',
        'export_format_preference',
        'validate_before_export',
        'entity_document_type',
        'entity_document_number',
        'entity_verification_digit',
        'entity_name',
        'entity_address',
        'entity_city',
        'entity_country',
        'responsible_name',
        'responsible_id',
        'responsible_position',
        'responsible_email',
        'responsible_phone',
        'auto_generate_on_period_close',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'fiscal_year' => 'integer',
        'minimum_reporting_amount' => 'decimal:2',
        'include_amounts_below_threshold' => 'boolean',
        'withholding_rates' => 'array',
        'concept_codes' => 'array',
        'validate_before_export' => 'boolean',
        'auto_generate_on_period_close' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = [
        'report_type_label',
        'is_configured',
    ];

    // Relationships
    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    // Scopes
    public function scopeForConjunto($query, int $conjuntoConfigId)
    {
        return $query->where('conjunto_config_id', $conjuntoConfigId);
    }

    public function scopeByFiscalYear($query, int $year)
    {
        return $query->where('fiscal_year', $year);
    }

    public function scopeByReportType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    public function scopeAutoGenerate($query)
    {
        return $query->where('auto_generate_on_period_close', true);
    }

    // Attributes
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

    public function getIsConfiguredAttribute(): bool
    {
        return ! empty($this->entity_document_number) &&
               ! empty($this->entity_name) &&
               ! empty($this->responsible_name) &&
               ! empty($this->responsible_email);
    }

    // Methods
    public static function getOrCreateForYear(int $conjuntoConfigId, int $fiscalYear, string $reportType): self
    {
        return self::firstOrCreate(
            [
                'conjunto_config_id' => $conjuntoConfigId,
                'fiscal_year' => $fiscalYear,
                'report_type' => $reportType,
            ],
            [
                'minimum_reporting_amount' => 100000000, // $100M default for 2025
                'include_amounts_below_threshold' => false,
                'export_format_preference' => 'xml',
                'validate_before_export' => true,
                'entity_document_type' => 'NIT',
                'entity_country' => 'Colombia',
                'auto_generate_on_period_close' => false,
                'withholding_rates' => self::getDefaultWithholdingRates($reportType),
                'concept_codes' => self::getDefaultConceptCodes($reportType),
            ]
        );
    }

    private static function getDefaultWithholdingRates(string $reportType): array
    {
        // Default withholding rates for Colombia 2025
        // These should be updated yearly based on DIAN regulations
        return match ($reportType) {
            '1001', '1003' => [
                '28' => 4.0,  // Servicios varios - 4%
                '29' => 11.0, // Honorarios - 11%
                '30' => 3.5,  // Arrendamientos - 3.5%
                '31' => 2.5,  // Gastos diversos - 2.5%
            ],
            '1647' => [
                '01' => 1.5,  // Retención especial 1.5%
            ],
            default => [],
        };
    }

    private static function getDefaultConceptCodes(string $reportType): array
    {
        // DIAN concept codes for different report types
        return match ($reportType) {
            '1001' => [
                '28' => 'Servicios',
                '29' => 'Honorarios',
                '30' => 'Arrendamientos',
                '31' => 'Otros pagos o abonos en cuenta',
            ],
            '1003' => [
                '01' => 'Retención en la fuente a título de renta',
                '02' => 'Retención en la fuente a título de IVA',
                '03' => 'Retención en la fuente a título de ICA',
            ],
            '1005' => [
                '01' => 'Cuotas de administración',
                '02' => 'Otros ingresos',
            ],
            '1647' => [
                '01' => 'Retención del 1.5%',
            ],
            default => [],
        };
    }

    public function getWithholdingRateForConcept(string $conceptCode): ?float
    {
        return $this->withholding_rates[$conceptCode] ?? null;
    }

    public function getConceptName(string $conceptCode): ?string
    {
        return $this->concept_codes[$conceptCode] ?? null;
    }

    public function updateEntityInfo(array $entityData): void
    {
        $this->update([
            'entity_document_type' => $entityData['document_type'] ?? $this->entity_document_type,
            'entity_document_number' => $entityData['document_number'] ?? $this->entity_document_number,
            'entity_verification_digit' => $entityData['verification_digit'] ?? $this->entity_verification_digit,
            'entity_name' => $entityData['name'] ?? $this->entity_name,
            'entity_address' => $entityData['address'] ?? $this->entity_address,
            'entity_city' => $entityData['city'] ?? $this->entity_city,
            'entity_country' => $entityData['country'] ?? $this->entity_country,
        ]);
    }

    public function updateResponsibleInfo(array $responsibleData): void
    {
        $this->update([
            'responsible_name' => $responsibleData['name'] ?? $this->responsible_name,
            'responsible_id' => $responsibleData['id'] ?? $this->responsible_id,
            'responsible_position' => $responsibleData['position'] ?? $this->responsible_position,
            'responsible_email' => $responsibleData['email'] ?? $this->responsible_email,
            'responsible_phone' => $responsibleData['phone'] ?? $this->responsible_phone,
        ]);
    }

    public function validate(): array
    {
        $errors = [];
        $warnings = [];

        // Validate entity information
        if (empty($this->entity_document_number)) {
            $errors[] = 'El NIT de la entidad es requerido';
        }

        if (empty($this->entity_name)) {
            $errors[] = 'El nombre de la entidad es requerido';
        }

        // Validate responsible information
        if (empty($this->responsible_name)) {
            $errors[] = 'El nombre del responsable es requerido';
        }

        if (empty($this->responsible_email)) {
            $errors[] = 'El email del responsable es requerido';
        } elseif (! filter_var($this->responsible_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'El email del responsable no es válido';
        }

        // Warnings for optional but recommended fields
        if (empty($this->entity_address)) {
            $warnings[] = 'Se recomienda incluir la dirección de la entidad';
        }

        if (empty($this->responsible_phone)) {
            $warnings[] = 'Se recomienda incluir el teléfono del responsable';
        }

        return [
            'is_valid' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
