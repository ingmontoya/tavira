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
        $defaultMappings = [
            'monthly_administration' => [
                'income_code' => '413501', // Cuotas de Administración
                'receivable_code' => '130501', // Cartera Administración
            ],
            'common_expense' => [
                'income_code' => '413501', // Cuotas de Administración
                'receivable_code' => '130501', // Cartera Administración
            ],
            'sanction' => [
                'income_code' => '413505', // Multas y Sanciones
                'receivable_code' => '130501', // Cartera Administración
            ],
            'parking' => [
                'income_code' => '413503', // Parqueaderos
                'receivable_code' => '130501', // Cartera Administración
            ],
            'late_fee' => [
                'income_code' => '413506', // Intereses de Mora
                'receivable_code' => '130503', // Cartera Intereses Mora
            ],
            'special' => [
                'income_code' => '413502', // Cuotas Extraordinarias
                'receivable_code' => '130502', // Cartera Cuotas Extraordinarias
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
