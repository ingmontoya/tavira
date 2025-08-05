<?php

namespace App\Services;

use App\Models\AccountingTransaction;
use App\Models\AccountingTransactionEntry;
use App\Models\ChartOfAccounts;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Servicio de validaciones contables avanzadas
 * 
 * Proporciona validaciones adicionales de integridad contable más allá
 * de las validaciones básicas de partida doble, incluyendo:
 * - Control de períodos cerrados
 * - Validación de naturaleza de cuentas
 * - Consistencia de referencias
 * - Validaciones específicas de propiedad horizontal
 */
class AccountingValidationService
{
    /**
     * Valida la integridad completa de una transacción contable
     * 
     * @param AccountingTransaction $transaction Transacción a validar
     * @return array Resultado de la validación con errores y advertencias
     */
    public function validateTransactionIntegrity(AccountingTransaction $transaction): array
    {
        $errors = [];
        $warnings = [];
        $info = [];

        // VALIDACIÓN 1: Partida doble básica (ya implementada pero verificamos)
        if (!$transaction->is_balanced) {
            $errors[] = "La transacción no cumple con la partida doble: Débitos({$transaction->total_debit}) ≠ Créditos({$transaction->total_credit})";
        }

        // VALIDACIÓN 2: Período cerrado
        $periodValidation = $this->validatePeriodOpen($transaction);
        if (!$periodValidation['is_valid']) {
            $errors[] = $periodValidation['message'];
        }

        // VALIDACIÓN 3: Naturaleza de las cuentas
        $natureValidation = $this->validateAccountNature($transaction);
        $errors = array_merge($errors, $natureValidation['errors']);
        $warnings = array_merge($warnings, $natureValidation['warnings']);

        // VALIDACIÓN 4: Cuentas que requieren terceros
        $thirdPartyValidation = $this->validateRequiredThirdParties($transaction);
        $errors = array_merge($errors, $thirdPartyValidation['errors']);
        $warnings = array_merge($warnings, $thirdPartyValidation['warnings']);

        // VALIDACIÓN 5: Consistencia de referencias
        $referenceValidation = $this->validateReferenceConsistency($transaction);
        $warnings = array_merge($warnings, $referenceValidation['warnings']);
        $info = array_merge($info, $referenceValidation['info']);

        // VALIDACIÓN 6: Validaciones específicas de propiedad horizontal
        $phValidation = $this->validatePropertyHorizontalRules($transaction);
        $warnings = array_merge($warnings, $phValidation['warnings']);
        $info = array_merge($info, $phValidation['info']);

        return [
            'is_valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
            'info' => $info,
            'validation_summary' => [
                'total_errors' => count($errors),
                'total_warnings' => count($warnings),
                'can_be_posted' => empty($errors),
                'requires_review' => !empty($warnings),
            ],
        ];
    }

    /**
     * Valida que el período de la transacción esté abierto
     */
    private function validatePeriodOpen(AccountingTransaction $transaction): array
    {
        $transactionDate = Carbon::parse($transaction->transaction_date);
        $month = $transactionDate->month;
        $year = $transactionDate->year;

        // TODO: Integrar con tabla de control de períodos cerrados cuando se implemente
        // Por ahora, usar regla simple: no permitir transacciones en períodos pasados de más de 3 meses
        $threeMonthsAgo = Carbon::now()->subMonths(3);
        
        if ($transactionDate->lt($threeMonthsAgo)) {
            return [
                'is_valid' => false,
                'message' => "No se permiten transacciones en períodos anteriores a {$threeMonthsAgo->format('m/Y')}. Fecha de transacción: {$transactionDate->format('d/m/Y')}",
            ];
        }

        // Validar que no sea una fecha futura más allá de 1 mes
        $oneMonthForward = Carbon::now()->addMonth();
        if ($transactionDate->gt($oneMonthForward)) {
            return [
                'is_valid' => false,
                'message' => "No se permiten transacciones con fecha futura más allá de {$oneMonthForward->format('m/Y')}. Fecha de transacción: {$transactionDate->format('d/m/Y')}",
            ];
        }

        return [
            'is_valid' => true,
            'message' => 'Período válido para registro de transacciones',
        ];
    }

    /**
     * Valida que los movimientos respeten la naturaleza de las cuentas
     */
    private function validateAccountNature(AccountingTransaction $transaction): array
    {
        $errors = [];
        $warnings = [];

        foreach ($transaction->entries as $entry) {
            $account = $entry->account;
            
            if (!$account) {
                $errors[] = "Movimiento con cuenta inexistente (ID: {$entry->account_id})";
                continue;
            }

            // Validar naturaleza de cuenta vs tipo de movimiento
            if ($account->nature === 'debit' && $entry->credit_amount > 0) {
                // Cuenta de naturaleza débito con movimiento crédito
                // Esto puede ser válido en algunos casos (ej: disminución de activo)
                // pero vale la pena advertir
                $warnings[] = "Cuenta '{$account->full_name}' (naturaleza débito) tiene movimiento crédito de $" . number_format($entry->credit_amount, 2);
            }

            if ($account->nature === 'credit' && $entry->debit_amount > 0) {
                // Cuenta de naturaleza crédito con movimiento débito
                // Similar al caso anterior
                $warnings[] = "Cuenta '{$account->full_name}' (naturaleza crédito) tiene movimiento débito de $" . number_format($entry->debit_amount, 2);
            }

            // Validar que la cuenta permita movimientos directos
            if (!$account->accepts_posting) {
                $errors[] = "La cuenta '{$account->full_name}' no permite movimientos directos (cuenta de agrupación)";
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Valida que las cuentas que requieren terceros los tengan asignados
     */
    private function validateRequiredThirdParties(AccountingTransaction $transaction): array
    {
        $errors = [];
        $warnings = [];

        foreach ($transaction->entries as $entry) {
            $account = $entry->account;
            
            if (!$account) continue;

            if ($account->requires_third_party && !$entry->third_party_id) {
                $errors[] = "La cuenta '{$account->full_name}' requiere tercero pero no tiene uno asignado";
            }

            // Validar que el tercero exista si está asignado
            if ($entry->third_party_id && $entry->third_party_type) {
                $thirdPartyExists = $this->validateThirdPartyExists($entry->third_party_type, $entry->third_party_id);
                if (!$thirdPartyExists) {
                    $errors[] = "Tercero {$entry->third_party_type}#{$entry->third_party_id} no existe en el sistema";
                }
            }
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * Valida la consistencia de las referencias de la transacción
     */
    private function validateReferenceConsistency(AccountingTransaction $transaction): array
    {
        $warnings = [];
        $info = [];

        if ($transaction->reference_type && $transaction->reference_id) {
            $referenceExists = $this->validateReferenceExists($transaction->reference_type, $transaction->reference_id);
            
            if (!$referenceExists) {
                $warnings[] = "La referencia {$transaction->reference_type}#{$transaction->reference_id} no existe en el sistema";
            } else {
                $info[] = "Referencia validada: {$transaction->reference_type}#{$transaction->reference_id}";
            }
        }

        // Validar coherencia entre descripción y referencia
        if ($transaction->reference_type === 'invoice' && $transaction->description) {
            if (!str_contains(strtolower($transaction->description), 'factura')) {
                $warnings[] = "Transacción referencia una factura pero la descripción no lo indica claramente";
            }
        }

        return [
            'warnings' => $warnings,
            'info' => $info,
        ];
    }

    /**
     * Validaciones específicas para el sector de propiedad horizontal
     */
    private function validatePropertyHorizontalRules(AccountingTransaction $transaction): array
    {
        $warnings = [];
        $info = [];

        // REGLA 1: Validar que movimientos en cartera tengan apartamento asociado
        $carteraEntries = $transaction->entries->filter(function ($entry) {
            return $entry->account && str_starts_with($entry->account->code, '1305'); // Cuentas de cartera
        });

        foreach ($carteraEntries as $entry) {
            if ($entry->third_party_type !== 'apartment') {
                $warnings[] = "Movimiento en cartera '{$entry->account->name}' no tiene apartamento asociado";
            }
        }

        // REGLA 2: Validar apropiaciones al fondo de reserva
        $reserveFundEntries = $transaction->entries->filter(function ($entry) {
            return $entry->account && $entry->account->code === '320501'; // Fondo de Reserva
        });

        if ($reserveFundEntries->isNotEmpty()) {
            $creditAmount = $reserveFundEntries->sum('credit_amount');
            if ($creditAmount > 0) {
                $info[] = "Apropiación al fondo de reserva: $" . number_format($creditAmount, 2);
                
                // Validar que haya un débito correspondiente en cuenta de gasto
                $expenseEntry = $transaction->entries->firstWhere('account.code', '530502');
                if (!$expenseEntry) {
                    $warnings[] = "Apropiación al fondo de reserva sin contrapartida en cuenta de gasto (530502)";
                }
            }
        }

        // REGLA 3: Validar que ingresos por cuotas tengan contrapartida en cartera
        $incomeEntries = $transaction->entries->filter(function ($entry) {
            return $entry->account && str_starts_with($entry->account->code, '4135'); // Ingresos operacionales
        });

        if ($incomeEntries->isNotEmpty() && $carteraEntries->isEmpty()) {
            $warnings[] = "Ingresos operacionales registrados sin contrapartida en cartera (posible error)";
        }

        return [
            'warnings' => $warnings,
            'info' => $info,
        ];
    }

    /**
     * Valida que un tercero exista en el sistema
     */
    private function validateThirdPartyExists(string $type, int $id): bool
    {
        return match ($type) {
            'apartment' => DB::table('apartments')->where('id', $id)->exists(),
            'supplier' => DB::table('suppliers')->where('id', $id)->exists(),
            'employee' => DB::table('employees')->where('id', $id)->exists(),
            default => false,
        };
    }

    /**
     * Valida que una referencia exista en el sistema
     */
    private function validateReferenceExists(string $type, int $id): bool
    {
        return match ($type) {
            'invoice' => DB::table('invoices')->where('id', $id)->exists(),
            'payment' => DB::table('payments')->where('id', $id)->exists(),
            'expense' => DB::table('expenses')->where('id', $id)->exists(),
            'reserve_fund_appropriation' => true, // Apropiaciones no tienen tabla específica
            default => false,
        };
    }

    /**
     * Ejecuta validaciones masivas sobre un conjunto de transacciones
     * 
     * @param Collection $transactions Colección de transacciones a validar
     * @return array Resumen de validaciones masivas
     */
    public function validateTransactionsBatch(Collection $transactions): array
    {
        $results = [
            'total_transactions' => $transactions->count(),
            'valid_transactions' => 0,
            'invalid_transactions' => 0,
            'transactions_with_warnings' => 0,
            'total_errors' => 0,
            'total_warnings' => 0,
            'validation_details' => [],
        ];

        foreach ($transactions as $transaction) {
            $validation = $this->validateTransactionIntegrity($transaction);
            
            if ($validation['is_valid']) {
                $results['valid_transactions']++;
            } else {
                $results['invalid_transactions']++;
            }

            if (!empty($validation['warnings'])) {
                $results['transactions_with_warnings']++;
            }

            $results['total_errors'] += count($validation['errors']);
            $results['total_warnings'] += count($validation['warnings']);

            $results['validation_details'][] = [
                'transaction_id' => $transaction->id,
                'transaction_number' => $transaction->transaction_number,
                'validation' => $validation,
            ];
        }

        return $results;
    }

    /**
     * Valida la integridad contable de un período completo
     * 
     * @param int $conjuntoConfigId ID del conjunto
     * @param int $month Mes a validar
     * @param int $year Año a validar
     * @return array Resultado de la validación del período
     */
    public function validatePeriodIntegrity(int $conjuntoConfigId, int $month, int $year): array
    {
        $transactions = AccountingTransaction::forConjunto($conjuntoConfigId)
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->where('status', 'contabilizado')
            ->with(['entries.account'])
            ->get();

        $batchValidation = $this->validateTransactionsBatch($transactions);

        // Validaciones adicionales a nivel de período
        $periodChecks = [
            'balance_check' => $this->validatePeriodBalance($conjuntoConfigId, $month, $year),
            'reserve_fund_check' => $this->validateReserveFundCompliance($conjuntoConfigId, $month, $year),
            'account_consistency_check' => $this->validateAccountConsistency($conjuntoConfigId, $month, $year),
        ];

        return array_merge($batchValidation, [
            'period_checks' => $periodChecks,
            'period' => "{$month}/{$year}",
            'conjunto_config_id' => $conjuntoConfigId,
        ]);
    }

    /**
     * Valida el balance del período
     */
    private function validatePeriodBalance(int $conjuntoConfigId, int $month, int $year): array
    {
        $totalDebits = DB::table('accounting_transaction_entries')
            ->join('accounting_transactions', 'accounting_transaction_entries.accounting_transaction_id', '=', 'accounting_transactions.id')
            ->where('accounting_transactions.conjunto_config_id', $conjuntoConfigId)
            ->whereMonth('accounting_transactions.transaction_date', $month)
            ->whereYear('accounting_transactions.transaction_date', $year)
            ->where('accounting_transactions.status', 'contabilizado')
            ->sum('accounting_transaction_entries.debit_amount');

        $totalCredits = DB::table('accounting_transaction_entries')
            ->join('accounting_transactions', 'accounting_transaction_entries.accounting_transaction_id', '=', 'accounting_transactions.id')
            ->where('accounting_transactions.conjunto_config_id', $conjuntoConfigId)
            ->whereMonth('accounting_transactions.transaction_date', $month)
            ->whereYear('accounting_transactions.transaction_date', $year)
            ->where('accounting_transactions.status', 'contabilizado')
            ->sum('accounting_transaction_entries.credit_amount');

        $difference = abs($totalDebits - $totalCredits);

        return [
            'is_balanced' => $difference <= 0.01,
            'total_debits' => (float) $totalDebits,
            'total_credits' => (float) $totalCredits,
            'difference' => (float) $difference,
            'status' => $difference <= 0.01 ? 'OK' : 'ERROR',
        ];
    }

    /**
     * Valida el cumplimiento del fondo de reserva
     */
    private function validateReserveFundCompliance(int $conjuntoConfigId, int $month, int $year): array
    {
        $reserveFundService = new ReserveFundService($conjuntoConfigId);
        return $reserveFundService->validateLegalCompliance($year);
    }

    /**
     * Valida la consistencia de las cuentas contables
     */
    private function validateAccountConsistency(int $conjuntoConfigId, int $month, int $year): array
    {
        // Verificar que no haya cuentas con saldos negativos incorrectos
        $accounts = ChartOfAccounts::forConjunto($conjuntoConfigId)->get();
        $inconsistencies = [];

        foreach ($accounts as $account) {
            $balance = $account->getBalance();
            
            // Activos y gastos no deberían tener saldo crédito (negativo)
            if (in_array($account->account_type, ['asset', 'expense']) && $balance < 0) {
                $inconsistencies[] = "Cuenta {$account->full_name} tiene saldo crédito: $" . number_format(abs($balance), 2);
            }
            
            // Pasivos, patrimonio e ingresos no deberían tener saldo débito (negativo)
            if (in_array($account->account_type, ['liability', 'equity', 'income']) && $balance < 0) {
                $inconsistencies[] = "Cuenta {$account->full_name} tiene saldo débito: $" . number_format(abs($balance), 2);
            }
        }

        return [
            'accounts_validated' => $accounts->count(),
            'inconsistencies' => $inconsistencies,
            'inconsistencies_count' => count($inconsistencies),
            'status' => empty($inconsistencies) ? 'OK' : 'WARNING',
        ];
    }
}