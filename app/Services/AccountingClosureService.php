<?php

namespace App\Services;

use App\Models\AccountingPeriodClosure;
use App\Models\AccountingTransaction;
use App\Models\ChartOfAccounts;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para cierre contable anual
 *
 * Ejecuta los asientos de cierre según normativa contable colombiana:
 * 1. Cierra cuentas de ingresos (4xxx) contra cuenta 5905 (Ganancias y Pérdidas)
 * 2. Cierra cuentas de gastos (5xxx) contra cuenta 5905
 * 3. Traslada resultado neto de 5905 a patrimonio (3605 o 3610)
 */
class AccountingClosureService
{
    private int $conjuntoConfigId;

    public function __construct(int $conjuntoConfigId)
    {
        $this->conjuntoConfigId = $conjuntoConfigId;
    }

    /**
     * Ejecuta el cierre contable de un período (mensual o anual)
     *
     * @param  string  $periodType  Tipo de período: 'monthly' o 'annual'
     * @param  int  $fiscalYear  Año fiscal a cerrar
     * @param  string  $closureDate  Fecha de cierre
     * @param  string  $periodStartDate  Fecha de inicio del período
     * @param  string  $periodEndDate  Fecha de fin del período
     * @param  array  $options  Opciones adicionales de cierre
     * @return array Resultado del cierre con detalles
     *
     * @throws \Exception Si hay errores en el proceso de cierre
     */
    public function executePeriodClosure(
        string $periodType,
        int $fiscalYear,
        string $closureDate,
        string $periodStartDate,
        string $periodEndDate,
        array $options = []
    ): array {
        DB::connection('tenant')->beginTransaction();

        try {
            $startTime = microtime(true);
            $closureDate = Carbon::parse($closureDate);
            $periodStartDate = Carbon::parse($periodStartDate);
            $periodEndDate = Carbon::parse($periodEndDate);

            Log::info("Iniciando cierre contable {$periodType}", [
                'conjunto_config_id' => $this->conjuntoConfigId,
                'fiscal_year' => $fiscalYear,
                'period_type' => $periodType,
                'period_start_date' => $periodStartDate->toDateString(),
                'period_end_date' => $periodEndDate->toDateString(),
                'closure_date' => $closureDate->toDateString(),
                'options' => $options,
            ]);

            // Validar precondiciones
            $this->validatePreconditions($fiscalYear, $periodType, $periodEndDate);

            // Crear registro de cierre en borrador
            $closure = AccountingPeriodClosure::create([
                'conjunto_config_id' => $this->conjuntoConfigId,
                'fiscal_year' => $fiscalYear,
                'period_type' => $periodType,
                'period_start_date' => $periodStartDate,
                'period_end_date' => $periodEndDate,
                'closure_date' => $closureDate,
                'status' => 'draft',
                'total_income' => 0,
                'total_expenses' => 0,
                'net_result' => 0,
                'closed_by' => auth()->id() ?? 1,
                'notes' => $options['notes'] ?? null,
            ]);

            $results = [
                'closure_id' => $closure->id,
                'conjunto_config_id' => $this->conjuntoConfigId,
                'fiscal_year' => $fiscalYear,
                'closure_date' => $closureDate->toDateString(),
                'start_time' => $startTime,
                'steps' => [],
                'errors' => [],
                'warnings' => [],
            ];

            // PASO 1: Cerrar cuentas de ingresos (4xxx) contra 5905
            $results['steps']['close_income_accounts'] = $this->closeIncomeAccounts(
                $fiscalYear,
                $closureDate,
                $periodStartDate,
                $periodEndDate
            );
            $totalIncome = $results['steps']['close_income_accounts']['total_income'];

            // PASO 2: Cerrar cuentas de gastos (5xxx) contra 5905
            $results['steps']['close_expense_accounts'] = $this->closeExpenseAccounts(
                $fiscalYear,
                $closureDate,
                $periodStartDate,
                $periodEndDate
            );
            $totalExpenses = $results['steps']['close_expense_accounts']['total_expenses'];

            // PASO 3: Trasladar resultado neto de 5905 a patrimonio
            $netResult = $totalIncome - $totalExpenses;
            $results['steps']['transfer_net_result'] = $this->transferNetResultToEquity(
                $fiscalYear,
                $closureDate,
                $netResult
            );

            // Actualizar registro de cierre
            $closure->update([
                'status' => 'completed',
                'total_income' => $totalIncome,
                'total_expenses' => $totalExpenses,
                'net_result' => $netResult,
                'closing_transaction_id' => $results['steps']['transfer_net_result']['transaction_id'],
            ]);

            $endTime = microtime(true);
            $results['end_time'] = $endTime;
            $results['duration_seconds'] = round($endTime - $startTime, 2);
            $results['success'] = true;
            $results['total_income'] = $totalIncome;
            $results['total_expenses'] = $totalExpenses;
            $results['net_result'] = $netResult;
            $results['is_profit'] = $netResult > 0;

            DB::connection('tenant')->commit();

            Log::info("Cierre contable {$periodType} completado exitosamente", [
                'conjunto_config_id' => $this->conjuntoConfigId,
                'fiscal_year' => $fiscalYear,
                'period_type' => $periodType,
                'period_start_date' => $periodStartDate->toDateString(),
                'period_end_date' => $periodEndDate->toDateString(),
                'duration' => $results['duration_seconds'],
                'net_result' => $netResult,
            ]);

            return $results;

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();

            Log::error("Error durante cierre contable {$periodType}", [
                'conjunto_config_id' => $this->conjuntoConfigId,
                'fiscal_year' => $fiscalYear,
                'period_type' => $periodType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Ejecuta el cierre contable anual completo (método de compatibilidad)
     *
     * @param  int  $fiscalYear  Año fiscal a cerrar
     * @param  string  $closureDate  Fecha de cierre (generalmente último día del año)
     * @param  array  $options  Opciones adicionales de cierre
     * @return array Resultado del cierre con detalles
     *
     * @throws \Exception Si hay errores en el proceso de cierre
     */
    public function executeAnnualClosure(int $fiscalYear, string $closureDate, array $options = []): array
    {
        $periodStartDate = Carbon::create($fiscalYear, 1, 1)->toDateString();
        $periodEndDate = Carbon::create($fiscalYear, 12, 31)->toDateString();

        return $this->executePeriodClosure(
            'annual',
            $fiscalYear,
            $closureDate,
            $periodStartDate,
            $periodEndDate,
            $options
        );
    }

    /**
     * Valida que el período puede ser cerrado
     */
    private function validatePreconditions(int $fiscalYear, string $periodType, Carbon $periodEndDate): void
    {
        // Validar que el período no esté ya cerrado
        $existingClosure = AccountingPeriodClosure::forConjunto($this->conjuntoConfigId)
            ->byFiscalYear($fiscalYear)
            ->where('period_type', $periodType)
            ->where('status', 'completed')
            ->first();

        $periodLabel = $periodType === 'monthly'
            ? "El período {$periodEndDate->format('F Y')}"
            : "El año fiscal {$fiscalYear}";

        if ($existingClosure) {
            throw new \Exception("{$periodLabel} ya está cerrado. Si necesita reversar el cierre, debe hacerlo primero.");
        }

        // Validar que no haya transacciones en borrador para el período
        // Para cierres anuales, revisar todo el año
        // Para cierres mensuales, revisar solo el mes
        if ($periodType === 'annual') {
            $periodStartDate = Carbon::create($fiscalYear, 1, 1);
            $periodEndDateCheck = Carbon::create($fiscalYear, 12, 31);
        } else {
            // Para cierres mensuales, usar el período específico
            $periodStartDate = $periodEndDate->copy()->startOfMonth();
            $periodEndDateCheck = $periodEndDate->copy()->endOfMonth();
        }

        $draftTransactions = AccountingTransaction::forConjunto($this->conjuntoConfigId)
            ->whereBetween('transaction_date', [$periodStartDate, $periodEndDateCheck])
            ->where('status', 'borrador')
            ->count();

        if ($draftTransactions > 0) {
            throw new \Exception("Existen {$draftTransactions} transacciones en borrador para {$periodLabel}. Deben ser contabilizadas o canceladas antes del cierre.");
        }

        // Validar que el período no sea futuro
        if ($periodEndDate->isFuture()) {
            throw new \Exception("No se puede cerrar un período futuro: {$periodEndDate->format('Y-m-d')}");
        }

        // Validar que la cuenta 5905 existe
        $account5905 = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', '590505')
            ->first();

        if (! $account5905) {
            throw new \Exception('No se encontró la cuenta 590505 (Ganancias y Pérdidas). Debe ejecutar el seeder de cuentas primero.');
        }

        // Validar que las cuentas de patrimonio existen
        $account3605 = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', '360505')
            ->first();

        $account3610 = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', '361005')
            ->first();

        if (! $account3605 || ! $account3610) {
            throw new \Exception('No se encontraron las cuentas de patrimonio 360505 (Excedente) o 361005 (Deficit). Debe ejecutar el seeder de cuentas primero.');
        }
    }

    /**
     * Cierra todas las cuentas de ingresos (4xxx) contra la cuenta 5905
     */
    private function closeIncomeAccounts(
        int $fiscalYear,
        Carbon $closureDate,
        Carbon $periodStartDate,
        Carbon $periodEndDate
    ): array {
        $startTime = microtime(true);

        // Obtener todas las cuentas de ingresos activas
        $incomeAccounts = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', 'LIKE', '4%')
            ->where('accepts_posting', true)
            ->active()
            ->get();

        if ($incomeAccounts->isEmpty()) {
            return [
                'status' => 'skipped',
                'duration' => round(microtime(true) - $startTime, 2),
                'message' => 'No hay cuentas de ingresos para cerrar',
                'total_income' => 0,
                'accounts_closed' => 0,
            ];
        }

        // Crear transacción de cierre de ingresos
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjuntoConfigId,
            'transaction_date' => $closureDate,
            'description' => "Cierre de cuentas de ingresos - Año Fiscal {$fiscalYear}",
            'reference_type' => 'accounting_closure',
            'reference_id' => null,
            'status' => 'borrador',
            'created_by' => auth()->id() ?? 1,
        ]);

        $totalIncome = 0;
        $accountsClosed = 0;

        // Obtener cuenta 5905
        $account5905 = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', '590505')
            ->firstOrFail();

        foreach ($incomeAccounts as $account) {
            // Calcular saldo de la cuenta para el año
            $balance = $account->getBalance(
                $periodStartDate->toDateString(),
                $periodEndDate->toDateString()
            );

            // Solo cerrar cuentas con saldo
            if (abs($balance) < 0.01) {
                continue;
            }

            // Las cuentas de ingreso tienen naturaleza crédito
            // Para cerrarlas, las debemos debitar
            $transaction->addEntry([
                'account_id' => $account->id,
                'description' => "Cierre cuenta {$account->code} - {$account->name}",
                'debit_amount' => abs($balance),
                'credit_amount' => 0,
            ]);

            $totalIncome += abs($balance);
            $accountsClosed++;
        }

        // Acreditar la cuenta 5905 por el total de ingresos
        if ($totalIncome > 0) {
            $transaction->addEntry([
                'account_id' => $account5905->id,
                'description' => 'Total ingresos del ejercicio',
                'debit_amount' => 0,
                'credit_amount' => $totalIncome,
            ]);

            // Contabilizar la transacción
            $transaction->post(skipPeriodValidation: true);
        } else {
            // Si no hay ingresos, cancelar la transacción
            $transaction->delete();
        }

        return [
            'status' => 'success',
            'duration' => round(microtime(true) - $startTime, 2),
            'transaction_id' => $totalIncome > 0 ? $transaction->id : null,
            'transaction_number' => $totalIncome > 0 ? $transaction->transaction_number : null,
            'total_income' => $totalIncome,
            'accounts_closed' => $accountsClosed,
            'message' => "Se cerraron {$accountsClosed} cuentas de ingresos por un total de $".number_format($totalIncome, 2),
        ];
    }

    /**
     * Cierra todas las cuentas de gastos (5xxx, excepto 5905) contra la cuenta 5905
     */
    private function closeExpenseAccounts(
        int $fiscalYear,
        Carbon $closureDate,
        Carbon $periodStartDate,
        Carbon $periodEndDate
    ): array {
        $startTime = microtime(true);

        // Obtener todas las cuentas de gastos activas, excluyendo la 5905
        $expenseAccounts = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', 'LIKE', '5%')
            ->where('code', 'NOT LIKE', '5905%') // Excluir la cuenta de cierre
            ->where('accepts_posting', true)
            ->active()
            ->get();

        if ($expenseAccounts->isEmpty()) {
            return [
                'status' => 'skipped',
                'duration' => round(microtime(true) - $startTime, 2),
                'message' => 'No hay cuentas de gastos para cerrar',
                'total_expenses' => 0,
                'accounts_closed' => 0,
            ];
        }

        // Crear transacción de cierre de gastos
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjuntoConfigId,
            'transaction_date' => $closureDate,
            'description' => "Cierre de cuentas de gastos - Año Fiscal {$fiscalYear}",
            'reference_type' => 'accounting_closure',
            'reference_id' => null,
            'status' => 'borrador',
            'created_by' => auth()->id() ?? 1,
        ]);

        $totalExpenses = 0;
        $accountsClosed = 0;

        // Obtener cuenta 5905
        $account5905 = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', '590505')
            ->firstOrFail();

        foreach ($expenseAccounts as $account) {
            // Calcular saldo de la cuenta para el año
            $balance = $account->getBalance(
                $periodStartDate->toDateString(),
                $periodEndDate->toDateString()
            );

            // Solo cerrar cuentas con saldo
            if (abs($balance) < 0.01) {
                continue;
            }

            // Las cuentas de gasto tienen naturaleza débito
            // Para cerrarlas, las debemos acreditar
            $transaction->addEntry([
                'account_id' => $account->id,
                'description' => "Cierre cuenta {$account->code} - {$account->name}",
                'debit_amount' => 0,
                'credit_amount' => abs($balance),
            ]);

            $totalExpenses += abs($balance);
            $accountsClosed++;
        }

        // Debitar la cuenta 5905 por el total de gastos
        if ($totalExpenses > 0) {
            $transaction->addEntry([
                'account_id' => $account5905->id,
                'description' => 'Total gastos del ejercicio',
                'debit_amount' => $totalExpenses,
                'credit_amount' => 0,
            ]);

            // Contabilizar la transacción
            $transaction->post(skipPeriodValidation: true);
        } else {
            // Si no hay gastos, cancelar la transacción
            $transaction->delete();
        }

        return [
            'status' => 'success',
            'duration' => round(microtime(true) - $startTime, 2),
            'transaction_id' => $totalExpenses > 0 ? $transaction->id : null,
            'transaction_number' => $totalExpenses > 0 ? $transaction->transaction_number : null,
            'total_expenses' => $totalExpenses,
            'accounts_closed' => $accountsClosed,
            'message' => "Se cerraron {$accountsClosed} cuentas de gastos por un total de $".number_format($totalExpenses, 2),
        ];
    }

    /**
     * Traslada el resultado neto de la cuenta 5905 a patrimonio (3605 o 3610)
     */
    private function transferNetResultToEquity(
        int $fiscalYear,
        Carbon $closureDate,
        float $netResult
    ): array {
        $startTime = microtime(true);

        if (abs($netResult) < 0.01) {
            return [
                'status' => 'skipped',
                'duration' => round(microtime(true) - $startTime, 2),
                'message' => 'No hay resultado neto para trasladar',
                'net_result' => 0,
            ];
        }

        $isProfitable = $netResult > 0;

        // Obtener cuentas necesarias
        $account5905 = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', '590505')
            ->firstOrFail();

        // Si hay utilidad, usar cuenta 3605 (Excedente del Ejercicio)
        // Si hay pérdida, usar cuenta 3610 (Deficit del Ejercicio)
        $equityAccountCode = $isProfitable ? '360505' : '361005';
        $equityAccount = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', $equityAccountCode)
            ->firstOrFail();

        // Crear transacción de traslado a patrimonio
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjuntoConfigId,
            'transaction_date' => $closureDate,
            'description' => "Traslado de ".($isProfitable ? 'excedente' : 'déficit')." del ejercicio {$fiscalYear} a patrimonio",
            'reference_type' => 'accounting_closure',
            'reference_id' => null,
            'status' => 'borrador',
            'created_by' => auth()->id() ?? 1,
        ]);

        if ($isProfitable) {
            // Si hay utilidad (saldo crédito en 5905):
            // Debitar 5905 (cerrar la cuenta)
            $transaction->addEntry([
                'account_id' => $account5905->id,
                'description' => 'Cierre cuenta Ganancias y Pérdidas - Excedente del ejercicio',
                'debit_amount' => abs($netResult),
                'credit_amount' => 0,
            ]);

            // Acreditar 3605 (aumentar patrimonio)
            $transaction->addEntry([
                'account_id' => $equityAccount->id,
                'description' => "Excedente del ejercicio {$fiscalYear}",
                'debit_amount' => 0,
                'credit_amount' => abs($netResult),
            ]);
        } else {
            // Si hay pérdida (saldo débito en 5905):
            // Acreditar 5905 (cerrar la cuenta)
            $transaction->addEntry([
                'account_id' => $account5905->id,
                'description' => 'Cierre cuenta Ganancias y Pérdidas - Déficit del ejercicio',
                'debit_amount' => 0,
                'credit_amount' => abs($netResult),
            ]);

            // Debitar 3610 (disminuir patrimonio)
            $transaction->addEntry([
                'account_id' => $equityAccount->id,
                'description' => "Déficit del ejercicio {$fiscalYear}",
                'debit_amount' => abs($netResult),
                'credit_amount' => 0,
            ]);
        }

        // Contabilizar la transacción
        $transaction->post(skipPeriodValidation: true);

        return [
            'status' => 'success',
            'duration' => round(microtime(true) - $startTime, 2),
            'transaction_id' => $transaction->id,
            'transaction_number' => $transaction->transaction_number,
            'net_result' => $netResult,
            'is_profitable' => $isProfitable,
            'equity_account' => $equityAccount->code.' - '.$equityAccount->name,
            'message' => ($isProfitable ? 'Excedente' : 'Déficit')." de $".number_format(abs($netResult), 2)." trasladado a ".$equityAccount->code,
        ];
    }

    /**
     * Reversa un cierre contable completado
     */
    public function reverseClosure(int $closureId): array
    {
        DB::connection('tenant')->beginTransaction();

        try {
            $closure = AccountingPeriodClosure::findOrFail($closureId);

            if (! $closure->canBeReversed()) {
                throw new \Exception('El cierre no puede ser reversado. Solo se pueden reversar cierres completados.');
            }

            if ($closure->conjunto_config_id !== $this->conjuntoConfigId) {
                throw new \Exception('El cierre no pertenece a este conjunto.');
            }

            // Cancelar la transacción de cierre si existe
            if ($closure->closing_transaction_id) {
                $closingTransaction = AccountingTransaction::find($closure->closing_transaction_id);
                if ($closingTransaction && $closingTransaction->can_be_cancelled) {
                    $closingTransaction->cancel();
                }
            }

            // Buscar y cancelar todas las transacciones relacionadas con el cierre
            $closureTransactions = AccountingTransaction::forConjunto($this->conjuntoConfigId)
                ->where('reference_type', 'accounting_closure')
                ->whereYear('transaction_date', $closure->fiscal_year)
                ->where('status', 'contabilizado')
                ->get();

            foreach ($closureTransactions as $transaction) {
                if ($transaction->can_be_cancelled) {
                    $transaction->cancel();
                }
            }

            // Marcar el cierre como reversado
            $closure->update([
                'status' => 'reversed',
            ]);

            DB::connection('tenant')->commit();

            Log::info('Cierre contable reversado exitosamente', [
                'closure_id' => $closure->id,
                'fiscal_year' => $closure->fiscal_year,
                'conjunto_config_id' => $this->conjuntoConfigId,
            ]);

            return [
                'success' => true,
                'closure_id' => $closure->id,
                'fiscal_year' => $closure->fiscal_year,
                'message' => "Cierre del año {$closure->fiscal_year} reversado exitosamente",
                'transactions_cancelled' => $closureTransactions->count(),
            ];

        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();

            Log::error('Error reversando cierre contable', [
                'closure_id' => $closureId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtiene el historial de cierres contables
     */
    public function getClosureHistory(?int $fiscalYear = null): array
    {
        $query = AccountingPeriodClosure::forConjunto($this->conjuntoConfigId)
            ->where('period_type', 'annual')
            ->with(['closedByUser', 'closingTransaction'])
            ->orderBy('fiscal_year', 'desc');

        if ($fiscalYear) {
            $query->byFiscalYear($fiscalYear);
        }

        return $query->get()->map(function ($closure) {
            return [
                'id' => $closure->id,
                'fiscal_year' => $closure->fiscal_year,
                'period_label' => $closure->period_label,
                'closure_date' => $closure->closure_date->format('Y-m-d'),
                'status' => $closure->status,
                'status_label' => $closure->status_label,
                'total_income' => (float) $closure->total_income,
                'total_expenses' => (float) $closure->total_expenses,
                'net_result' => (float) $closure->net_result,
                'is_profit' => $closure->is_profit,
                'closed_by' => $closure->closedByUser ? $closure->closedByUser->name : null,
                'closing_transaction_number' => $closure->closingTransaction ? $closure->closingTransaction->transaction_number : null,
                'notes' => $closure->notes,
            ];
        })->toArray();
    }

    /**
     * Vista previa del cierre sin ejecutarlo (para un período específico)
     */
    public function previewClosureForPeriod(string $periodStartDate, string $periodEndDate): array
    {
        $periodStartDate = Carbon::parse($periodStartDate);
        $periodEndDate = Carbon::parse($periodEndDate);

        // Calcular totales de ingresos
        $incomeAccounts = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', 'LIKE', '4%')
            ->where('accepts_posting', true)
            ->active()
            ->get();

        $totalIncome = 0;
        $incomeDetails = [];

        foreach ($incomeAccounts as $account) {
            $balance = $account->getBalance(
                $periodStartDate->toDateString(),
                $periodEndDate->toDateString()
            );

            if (abs($balance) >= 0.01) {
                $incomeDetails[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => abs($balance),
                ];
                $totalIncome += abs($balance);
            }
        }

        // Calcular totales de gastos
        $expenseAccounts = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', 'LIKE', '5%')
            ->where('code', 'NOT LIKE', '5905%')
            ->where('accepts_posting', true)
            ->active()
            ->get();

        $totalExpenses = 0;
        $expenseDetails = [];

        foreach ($expenseAccounts as $account) {
            $balance = $account->getBalance(
                $periodStartDate->toDateString(),
                $periodEndDate->toDateString()
            );

            if (abs($balance) >= 0.01) {
                $expenseDetails[] = [
                    'code' => $account->code,
                    'name' => $account->name,
                    'balance' => abs($balance),
                ];
                $totalExpenses += abs($balance);
            }
        }

        $netResult = $totalIncome - $totalExpenses;

        return [
            'fiscal_year' => $periodStartDate->year,
            'period_start_date' => $periodStartDate->format('Y-m-d'),
            'period_end_date' => $periodEndDate->format('Y-m-d'),
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_result' => $netResult,
            'is_profit' => $netResult > 0,
            'income_accounts_count' => count($incomeDetails),
            'expense_accounts_count' => count($expenseDetails),
            'income_details' => $incomeDetails,
            'expense_details' => $expenseDetails,
            'can_close' => true,
            'warnings' => [],
        ];
    }

    /**
     * Vista previa del cierre sin ejecutarlo (método de compatibilidad para cierres anuales)
     */
    public function previewClosure(int $fiscalYear): array
    {
        $periodStartDate = Carbon::create($fiscalYear, 1, 1)->toDateString();
        $periodEndDate = Carbon::create($fiscalYear, 12, 31)->toDateString();

        return $this->previewClosureForPeriod($periodStartDate, $periodEndDate);
    }
}
