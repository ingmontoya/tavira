<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentConceptAccountMapping extends Model
{
    protected $fillable = [
        'payment_concept_id',
        'income_account_id',
        'receivable_account_id',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function paymentConcept(): BelongsTo
    {
        return $this->belongsTo(PaymentConcept::class);
    }

    public function incomeAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccounts::class, 'income_account_id');
    }

    public function receivableAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccounts::class, 'receivable_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForConcept($query, int $conceptId)
    {
        return $query->where('payment_concept_id', $conceptId);
    }

    public static function getAccountsForConcept(int $conceptId): ?array
    {
        $mapping = self::active()
            ->forConcept($conceptId)
            ->with(['incomeAccount', 'receivableAccount'])
            ->first();

        if (! $mapping) {
            return self::getDefaultAccountsForConceptType($conceptId);
        }

        return [
            'income_account' => $mapping->incomeAccount,
            'receivable_account' => $mapping->receivableAccount,
        ];
    }

    private static function getDefaultAccountsForConceptType(int $conceptId): ?array
    {
        $concept = PaymentConcept::find($conceptId);

        if (! $concept) {
            return null;
        }

        // Default account mappings based on payment concept type
        // Using Colombian PUC codes for property management
        $defaultMappings = [
            'monthly_administration' => [
                'income_code' => '417005', // Cuotas de Administración
                'receivable_code' => '13050505', // Cartera Administración
            ],
            'common_expense' => [
                'income_code' => '417005', // Cuotas de Administración
                'receivable_code' => '13050505', // Cartera Administración
            ],
            'sanction' => [
                'income_code' => '417005', // Cuotas de Administración (Multas se incluyen aquí)
                'receivable_code' => '13050525', // Sanciones Asamblea
            ],
            'parking' => [
                'income_code' => '417005', // Cuotas de Administración (incluye parqueaderos)
                'receivable_code' => '13050530', // Uso Zonas Comunes
            ],
            'late_fee' => [
                'income_code' => '417010', // Intereses de Mora Cuotas de Administración
                'receivable_code' => '13050510', // Intereses de Mora Cuotas de Administración
            ],
            'special' => [
                'income_code' => '417015', // Cuota Extra para Fachadas
                'receivable_code' => '13050515', // Cuota Extra para Fachadas
            ],
        ];

        $mapping = $defaultMappings[$concept->type] ?? $defaultMappings['common_expense'];

        return [
            'income_account' => ChartOfAccounts::where('code', $mapping['income_code'])->first(),
            'receivable_account' => ChartOfAccounts::where('code', $mapping['receivable_code'])->first(),
        ];
    }

    public static function createDefaultMappings(): void
    {
        $concepts = PaymentConcept::all();
        $createdCount = 0;
        $skippedCount = 0;

        foreach ($concepts as $concept) {
            if (self::where('payment_concept_id', $concept->id)->exists()) {
                $skippedCount++;

                continue; // Skip if mapping already exists
            }

            $defaultAccounts = self::getDefaultAccountsForConceptType($concept->id);

            if ($defaultAccounts && $defaultAccounts['income_account'] && $defaultAccounts['receivable_account']) {
                self::create([
                    'payment_concept_id' => $concept->id,
                    'income_account_id' => $defaultAccounts['income_account']->id,
                    'receivable_account_id' => $defaultAccounts['receivable_account']->id,
                    'is_active' => true,
                    'notes' => 'Mapeo automático basado en tipo de concepto: '.$concept->type,
                ]);
                $createdCount++;
            }
        }

        if (app()->runningInConsole()) {
            echo "✅ Mapeos contables procesados: {$createdCount} creados, {$skippedCount} ya existían.\n";
        }
    }
}
