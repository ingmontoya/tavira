<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethodAccountMapping extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'payment_method',
        'cash_account_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccounts::class, 'cash_account_id');
    }

    public static function getCashAccountForPaymentMethod(int $conjuntoConfigId, string $paymentMethod): ?ChartOfAccounts
    {
        $mapping = self::where('conjunto_config_id', $conjuntoConfigId)
            ->where('payment_method', $paymentMethod)
            ->where('is_active', true)
            ->with('cashAccount')
            ->first();

        if ($mapping) {
            return $mapping->cashAccount;
        }

        // Fallback: buscar mapeo para bank_transfer como default
        $defaultMapping = self::where('conjunto_config_id', $conjuntoConfigId)
            ->where('payment_method', 'bank_transfer')
            ->where('is_active', true)
            ->with('cashAccount')
            ->first();

        return $defaultMapping?->cashAccount;
    }

    public static function getAvailablePaymentMethods(): array
    {
        return [
            'cash' => 'Efectivo',
            'bank_transfer' => 'Transferencia Bancaria',
            'check' => 'Cheque',
            'credit_card' => 'Tarjeta de Crédito',
            'debit_card' => 'Tarjeta Débito',
            'online' => 'Pago en Línea',
            'pse' => 'PSE',
            'other' => 'Otro',
        ];
    }
}
