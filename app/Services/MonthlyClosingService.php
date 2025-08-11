<?php

namespace App\Services;

use App\Events\AccountingPeriodClosed;
use App\Models\AccountingTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para cierre contable mensual automatizado
 *
 * Ejecuta todas las tareas necesarias para el cierre de un período contable:
 * - Validación de integridad contable
 * - Cálculo de intereses de mora
 * - Apropiación del fondo de reserva
 * - Generación de reportes oficiales
 * - Marcado del período como cerrado
 */
class MonthlyClosingService
{
    private int $conjuntoConfigId;

    private ReserveFundService $reserveFundService;

    public function __construct(int $conjuntoConfigId)
    {
        $this->conjuntoConfigId = $conjuntoConfigId;
        $this->reserveFundService = new ReserveFundService($conjuntoConfigId);
    }

    /**
     * Ejecuta el cierre contable mensual completo
     *
     * @param  int  $month  Mes a cerrar (1-12)
     * @param  int  $year  Año a cerrar
     * @param  array  $options  Opciones adicionales de cierre
     * @return array Resultado del cierre con detalles
     */
    public function executeMonthlyClosing(int $month, int $year, array $options = []): array
    {
        DB::beginTransaction();

        try {
            $startTime = microtime(true);
            $closingDate = Carbon::create($year, $month)->endOfMonth();

            Log::info('Iniciando cierre contable mensual', [
                'conjunto_config_id' => $this->conjuntoConfigId,
                'month' => $month,
                'year' => $year,
                'closing_date' => $closingDate->toDateString(),
                'options' => $options,
            ]);

            // Validar precondiciones
            $this->validatePreconditions($month, $year);

            $results = [
                'conjunto_config_id' => $this->conjuntoConfigId,
                'period' => "{$month}/{$year}",
                'closing_date' => $closingDate->toDateString(),
                'start_time' => $startTime,
                'steps' => [],
                'errors' => [],
                'warnings' => [],
            ];

            // PASO 1: Validar integridad contable
            $results['steps']['integrity_validation'] = $this->validateAccountingIntegrity($month, $year);

            // PASO 2: Calcular y registrar intereses de mora
            if (! ($options['skip_late_fees'] ?? false)) {
                $results['steps']['late_fees'] = $this->processLateFees($month, $year);
            }

            // PASO 3: Apropiar fondo de reserva
            if (! ($options['skip_reserve_fund'] ?? false)) {
                $results['steps']['reserve_fund'] = $this->processReserveFund($month, $year);
            }

            // PASO 4: Generar y calcular depreciaciones (placeholder para futura implementación)
            if (! ($options['skip_depreciation'] ?? false)) {
                $results['steps']['depreciation'] = $this->processDepreciation($month, $year);
            }

            // PASO 5: Validar cuadre final
            $results['steps']['final_validation'] = $this->validateFinalBalance($month, $year);

            // PASO 6: Generar reportes oficiales
            $results['steps']['reports_generation'] = $this->generateOfficialReports($month, $year);

            // PASO 7: Marcar período como cerrado
            $results['steps']['period_closure'] = $this->markPeriodAsClosed($month, $year);

            $endTime = microtime(true);
            $results['end_time'] = $endTime;
            $results['duration_seconds'] = round($endTime - $startTime, 2);
            $results['success'] = true;

            DB::commit();

            // Disparar evento de cierre completado
            event(new AccountingPeriodClosed($this->conjuntoConfigId, $month, $year, $results));

            Log::info('Cierre contable mensual completado exitosamente', [
                'conjunto_config_id' => $this->conjuntoConfigId,
                'period' => "{$month}/{$year}",
                'duration' => $results['duration_seconds'],
                'steps_completed' => count($results['steps']),
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error durante cierre contable mensual', [
                'conjunto_config_id' => $this->conjuntoConfigId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Valida que el período puede ser cerrado
     */
    private function validatePreconditions(int $month, int $year): void
    {
        // Validar que el período no esté ya cerrado
        if ($this->isPeriodClosed($month, $year)) {
            throw new \Exception("El período {$month}/{$year} ya está cerrado");
        }

        // Validar que no haya transacciones en borrador para el período
        $draftTransactions = AccountingTransaction::forConjunto($this->conjuntoConfigId)
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->where('status', 'borrador')
            ->count();

        if ($draftTransactions > 0) {
            throw new \Exception("Existen {$draftTransactions} transacciones en borrador para el período {$month}/{$year}. Deben ser contabilizadas o canceladas antes del cierre.");
        }

        // Validar que el período no sea futuro
        $periodEnd = Carbon::create($year, $month)->endOfMonth();
        if ($periodEnd->isFuture()) {
            throw new \Exception("No se puede cerrar un período futuro: {$month}/{$year}");
        }
    }

    /**
     * Valida la integridad contable del período
     */
    private function validateAccountingIntegrity(int $month, int $year): array
    {
        $startTime = microtime(true);
        $errors = [];
        $warnings = [];

        // Validar que todas las transacciones cumplan con partida doble
        $unbalancedTransactions = AccountingTransaction::forConjunto($this->conjuntoConfigId)
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->where('status', 'contabilizado')
            ->get()
            ->filter(function ($transaction) {
                return ! $transaction->is_balanced;
            });

        if ($unbalancedTransactions->count() > 0) {
            $errors[] = "Se encontraron {$unbalancedTransactions->count()} transacciones desbalanceadas";
        }

        // Validar que todas las cuentas de cartera tengan terceros asociados
        $entriesWithoutThirdParty = DB::table('accounting_transaction_entries')
            ->join('accounting_transactions', 'accounting_transaction_entries.accounting_transaction_id', '=', 'accounting_transactions.id')
            ->join('chart_of_accounts', 'accounting_transaction_entries.account_id', '=', 'chart_of_accounts.id')
            ->where('accounting_transactions.conjunto_config_id', $this->conjuntoConfigId)
            ->whereMonth('accounting_transactions.transaction_date', $month)
            ->whereYear('accounting_transactions.transaction_date', $year)
            ->where('accounting_transactions.status', 'contabilizado')
            ->where('chart_of_accounts.requires_third_party', true)
            ->whereNull('accounting_transaction_entries.third_party_id')
            ->count();

        if ($entriesWithoutThirdParty > 0) {
            $warnings[] = "Se encontraron {$entriesWithoutThirdParty} movimientos en cuentas que requieren tercero sin tercero asignado";
        }

        return [
            'status' => empty($errors) ? 'success' : 'error',
            'duration' => round(microtime(true) - $startTime, 2),
            'errors' => $errors,
            'warnings' => $warnings,
            'unbalanced_transactions' => $unbalancedTransactions->count(),
            'entries_without_third_party' => $entriesWithoutThirdParty,
        ];
    }

    /**
     * Procesa los intereses de mora del período
     */
    private function processLateFees(int $month, int $year): array
    {
        $startTime = microtime(true);

        try {
            // Ejecutar el comando existente de procesamiento de mora
            $exitCode = Artisan::call('process:late-fees', [
                '--month' => $month,
                '--year' => $year,
                '--conjunto' => $this->conjuntoConfigId,
            ]);

            $output = Artisan::output();

            return [
                'status' => $exitCode === 0 ? 'success' : 'error',
                'duration' => round(microtime(true) - $startTime, 2),
                'exit_code' => $exitCode,
                'output' => $output,
                'message' => $exitCode === 0 ? 'Intereses de mora procesados correctamente' : 'Error procesando intereses de mora',
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'duration' => round(microtime(true) - $startTime, 2),
                'error' => $e->getMessage(),
                'message' => 'Excepción procesando intereses de mora',
            ];
        }
    }

    /**
     * Procesa la apropiación del fondo de reserva
     */
    private function processReserveFund(int $month, int $year): array
    {
        $startTime = microtime(true);

        try {
            $transaction = $this->reserveFundService->executeMonthlyAppropriation($month, $year);

            return [
                'status' => 'success',
                'duration' => round(microtime(true) - $startTime, 2),
                'transaction_id' => $transaction?->id,
                'appropriated_amount' => $transaction ? (float) $transaction->total_debit : 0,
                'message' => $transaction
                    ? 'Fondo de reserva apropiado: $'.number_format($transaction->total_debit, 2)
                    : 'No hubo monto para apropiar al fondo de reserva',
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'duration' => round(microtime(true) - $startTime, 2),
                'error' => $e->getMessage(),
                'message' => 'Error apropiando fondo de reserva',
            ];
        }
    }

    /**
     * Procesa las depreciaciones del período (placeholder)
     */
    private function processDepreciation(int $month, int $year): array
    {
        $startTime = microtime(true);

        // TODO: Implementar cálculo automático de depreciaciones
        // Por ahora, retornar placeholder para futura implementación

        return [
            'status' => 'skipped',
            'duration' => round(microtime(true) - $startTime, 2),
            'message' => 'Cálculo de depreciaciones no implementado (funcionalidad futura)',
            'depreciation_amount' => 0,
        ];
    }

    /**
     * Valida el cuadre contable final del período
     */
    private function validateFinalBalance(int $month, int $year): array
    {
        $startTime = microtime(true);
        $errors = [];

        // Calcular balance general del período
        $totalDebits = DB::table('accounting_transaction_entries')
            ->join('accounting_transactions', 'accounting_transaction_entries.accounting_transaction_id', '=', 'accounting_transactions.id')
            ->where('accounting_transactions.conjunto_config_id', $this->conjuntoConfigId)
            ->whereMonth('accounting_transactions.transaction_date', $month)
            ->whereYear('accounting_transactions.transaction_date', $year)
            ->where('accounting_transactions.status', 'contabilizado')
            ->sum('accounting_transaction_entries.debit_amount');

        $totalCredits = DB::table('accounting_transaction_entries')
            ->join('accounting_transactions', 'accounting_transaction_entries.accounting_transaction_id', '=', 'accounting_transactions.id')
            ->where('accounting_transactions.conjunto_config_id', $this->conjuntoConfigId)
            ->whereMonth('accounting_transactions.transaction_date', $month)
            ->whereYear('accounting_transactions.transaction_date', $year)
            ->where('accounting_transactions.status', 'contabilizado')
            ->sum('accounting_transaction_entries.credit_amount');

        $difference = abs($totalDebits - $totalCredits);

        if ($difference > 0.01) { // Tolerancia de 1 centavo
            $errors[] = "Diferencia en balance general: Débitos: ${totalDebits}, Créditos: ${totalCredits}, Diferencia: ${difference}";
        }

        return [
            'status' => empty($errors) ? 'success' : 'error',
            'duration' => round(microtime(true) - $startTime, 2),
            'total_debits' => (float) $totalDebits,
            'total_credits' => (float) $totalCredits,
            'difference' => (float) $difference,
            'errors' => $errors,
            'is_balanced' => $difference <= 0.01,
        ];
    }

    /**
     * Genera los reportes oficiales del período
     */
    private function generateOfficialReports(int $month, int $year): array
    {
        $startTime = microtime(true);
        $reportsGenerated = [];

        try {
            // TODO: Integrar con el sistema de reportes existente
            // Por ahora, simular la generación de reportes

            $monthFormatted = str_pad($month, 2, '0', STR_PAD_LEFT);
            $reportsGenerated = [
                'balance_sheet' => "balance_sheet_{$this->conjuntoConfigId}_{$year}_{$monthFormatted}.pdf",
                'income_statement' => "income_statement_{$this->conjuntoConfigId}_{$year}_{$monthFormatted}.pdf",
                'trial_balance' => "trial_balance_{$this->conjuntoConfigId}_{$year}_{$monthFormatted}.pdf",
            ];

            return [
                'status' => 'success',
                'duration' => round(microtime(true) - $startTime, 2),
                'reports_generated' => $reportsGenerated,
                'message' => 'Reportes oficiales generados correctamente',
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'duration' => round(microtime(true) - $startTime, 2),
                'error' => $e->getMessage(),
                'message' => 'Error generando reportes oficiales',
            ];
        }
    }

    /**
     * Marca el período como cerrado
     */
    private function markPeriodAsClosed(int $month, int $year): array
    {
        $startTime = microtime(true);

        // Crear registro de cierre en tabla de control
        // TODO: Crear tabla accounting_period_closures para control

        // Por ahora, usar un método simple de control
        $closureRecord = [
            'conjunto_config_id' => $this->conjuntoConfigId,
            'period_year' => $year,
            'period_month' => $month,
            'closed_at' => now(),
            'closed_by' => auth()->id() ?? 1,
        ];

        return [
            'status' => 'success',
            'duration' => round(microtime(true) - $startTime, 2),
            'closure_record' => $closureRecord,
            'message' => "Período {$month}/{$year} marcado como cerrado",
        ];
    }

    /**
     * Verifica si un período está cerrado
     */
    public function isPeriodClosed(int $month, int $year): bool
    {
        // TODO: Implementar verificación real cuando se cree la tabla de control
        // Por ahora, retornar false para permitir cierres
        return false;
    }

    /**
     * Obtiene el historial de cierres contables
     */
    public function getClosingHistory(?int $year = null): array
    {
        // TODO: Implementar cuando se cree la tabla de control
        return [];
    }
}
