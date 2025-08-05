<?php

namespace App\Services;

use App\Models\AccountingTransaction;
use App\Models\ChartOfAccounts;
use App\Settings\PaymentSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para manejo automático del Fondo de Reserva
 * Cumple con Ley 675 de 2001 - Propiedad Horizontal
 *
 * Funcionalidades:
 * - Cálculo automático del 30% mínimo legal
 * - Apropiación mensual automática
 * - Integración con sistema contable existente
 * - Trazabilidad completa de movimientos
 */
class ReserveFundService
{
    private int $conjuntoConfigId;

    private PaymentSettings $paymentSettings;

    public function __construct(int $conjuntoConfigId)
    {
        $this->conjuntoConfigId = $conjuntoConfigId;
        $this->paymentSettings = app(PaymentSettings::class);
    }

    /**
     * Calcula el monto del fondo de reserva para un mes específico
     * Basado en los ingresos operacionales del período
     *
     * @param  int  $month  Mes (1-12)
     * @param  int  $year  Año
     * @return float Monto a apropiar para reserva
     */
    public function calculateMonthlyReserve(int $month, int $year): float
    {
        try {
            // Obtener ingresos operacionales del mes (cuentas 4xxx)
            $monthlyIncome = $this->getOperationalIncomeForMonth($month, $year);

            // Porcentaje configurable (por defecto 30% según Ley 675)
            $reservePercentage = $this->paymentSettings->get('reserve_fund_percentage', 30) / 100;

            $reserveAmount = $monthlyIncome * $reservePercentage;

            Log::info('Cálculo fondo de reserva', [
                'conjunto_config_id' => $this->conjuntoConfigId,
                'month' => $month,
                'year' => $year,
                'monthly_income' => $monthlyIncome,
                'reserve_percentage' => $reservePercentage * 100,
                'calculated_reserve' => $reserveAmount,
            ]);

            return round($reserveAmount, 2);

        } catch (\Exception $e) {
            Log::error('Error calculando fondo de reserva', [
                'conjunto_config_id' => $this->conjuntoConfigId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Ejecuta la apropiación automática del fondo de reserva
     * Crea el asiento contable correspondiente
     *
     * @param  int  $month  Mes
     * @param  int  $year  Año
     * @return AccountingTransaction|null Transacción creada o null si no hay monto a apropiar
     */
    public function executeMonthlyAppropriation(int $month, int $year): ?AccountingTransaction
    {
        DB::beginTransaction();

        try {
            // Validar que no exista apropiación previa para el período
            if ($this->hasExistingAppropriation($month, $year)) {
                Log::info('Apropiación de reserva ya existe para el período', [
                    'conjunto_config_id' => $this->conjuntoConfigId,
                    'month' => $month,
                    'year' => $year,
                ]);

                DB::rollBack();

                return null;
            }

            // Calcular monto a apropiar
            $reserveAmount = $this->calculateMonthlyReserve($month, $year);

            // Si no hay monto a apropiar, terminar
            if ($reserveAmount <= 0) {
                Log::info('No hay monto para apropiar al fondo de reserva', [
                    'conjunto_config_id' => $this->conjuntoConfigId,
                    'month' => $month,
                    'year' => $year,
                ]);

                DB::rollBack();

                return null;
            }

            // Crear transacción contable
            $transaction = $this->createReserveFundTransaction($reserveAmount, $month, $year);

            DB::commit();

            Log::info('Apropiación de fondo de reserva ejecutada exitosamente', [
                'transaction_id' => $transaction->id,
                'amount' => $reserveAmount,
                'month' => $month,
                'year' => $year,
            ]);

            return $transaction;

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error ejecutando apropiación de fondo de reserva', [
                'conjunto_config_id' => $this->conjuntoConfigId,
                'month' => $month,
                'year' => $year,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Obtiene el balance actual del fondo de reserva
     *
     * @param  string|null  $asOfDate  Fecha de corte (opcional)
     * @return float Saldo del fondo de reserva
     */
    public function getReserveFundBalance(?string $asOfDate = null): float
    {
        $reserveFundAccount = $this->getReserveFundAccount();

        return $reserveFundAccount->getBalance(null, $asOfDate);
    }

    /**
     * Obtiene el historial de apropiaciones del fondo de reserva
     *
     * @param  int  $year  Año (opcional)
     */
    public function getAppropriationHistory(?int $year = null): \Illuminate\Database\Eloquent\Collection
    {
        $query = AccountingTransaction::forConjunto($this->conjuntoConfigId)
            ->where('description', 'LIKE', '%apropiación mensual fondo de reserva%')
            ->where('status', 'contabilizado')
            ->orderBy('transaction_date', 'desc');

        if ($year) {
            $query->whereYear('transaction_date', $year);
        }

        return $query->with(['entries.account'])->get();
    }

    /**
     * Valida si el conjunto cumple con el porcentaje mínimo legal
     *
     * @param  int  $year  Año a evaluar
     * @return array Resultado de la validación
     */
    public function validateLegalCompliance(int $year): array
    {
        $totalIncome = $this->getOperationalIncomeForYear($year);
        $totalAppropriated = $this->getTotalAppropriatedForYear($year);

        $minimumRequired = $totalIncome * 0.30; // 30% mínimo legal
        $compliancePercentage = $totalIncome > 0 ? ($totalAppropriated / $totalIncome) * 100 : 0;

        return [
            'year' => $year,
            'total_income' => $totalIncome,
            'total_appropriated' => $totalAppropriated,
            'minimum_required' => $minimumRequired,
            'compliance_percentage' => round($compliancePercentage, 2),
            'is_compliant' => $totalAppropriated >= $minimumRequired,
            'deficit' => max(0, $minimumRequired - $totalAppropriated),
        ];
    }

    /* ========== MÉTODOS PRIVADOS ========== */

    /**
     * Obtiene los ingresos operacionales para un mes específico
     */
    private function getOperationalIncomeForMonth(int $month, int $year): float
    {
        return AccountingTransaction::forConjunto($this->conjuntoConfigId)
            ->posted() // Solo transacciones contabilizadas
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->join('accounting_transaction_entries', 'accounting_transactions.id', '=', 'accounting_transaction_entries.accounting_transaction_id')
            ->join('chart_of_accounts', 'accounting_transaction_entries.account_id', '=', 'chart_of_accounts.id')
            ->where('chart_of_accounts.account_type', 'income')
            ->where('chart_of_accounts.code', 'LIKE', '41%') // Solo ingresos operacionales
            ->sum('accounting_transaction_entries.credit_amount');
    }

    /**
     * Obtiene los ingresos operacionales para un año completo
     */
    private function getOperationalIncomeForYear(int $year): float
    {
        return AccountingTransaction::forConjunto($this->conjuntoConfigId)
            ->posted()
            ->whereYear('transaction_date', $year)
            ->join('accounting_transaction_entries', 'accounting_transactions.id', '=', 'accounting_transaction_entries.accounting_transaction_id')
            ->join('chart_of_accounts', 'accounting_transaction_entries.account_id', '=', 'chart_of_accounts.id')
            ->where('chart_of_accounts.account_type', 'income')
            ->where('chart_of_accounts.code', 'LIKE', '41%')
            ->sum('accounting_transaction_entries.credit_amount');
    }

    /**
     * Obtiene el total apropiado al fondo de reserva en un año
     */
    private function getTotalAppropriatedForYear(int $year): float
    {
        $reserveFundAccount = $this->getReserveFundAccount();

        return AccountingTransaction::forConjunto($this->conjuntoConfigId)
            ->posted()
            ->whereYear('transaction_date', $year)
            ->join('accounting_transaction_entries', 'accounting_transactions.id', '=', 'accounting_transaction_entries.accounting_transaction_id')
            ->where('accounting_transaction_entries.account_id', $reserveFundAccount->id)
            ->sum('accounting_transaction_entries.credit_amount');
    }

    /**
     * Verifica si ya existe una apropiación para el período
     */
    private function hasExistingAppropriation(int $month, int $year): bool
    {
        return AccountingTransaction::forConjunto($this->conjuntoConfigId)
            ->where('description', 'LIKE', '%apropiación mensual fondo de reserva%')
            ->whereMonth('transaction_date', $month)
            ->whereYear('transaction_date', $year)
            ->where('status', 'contabilizado')
            ->exists();
    }

    /**
     * Crea la transacción contable para la apropiación del fondo de reserva
     */
    private function createReserveFundTransaction(float $amount, int $month, int $year): AccountingTransaction
    {
        // Obtener cuentas necesarias
        $expenseAccount = $this->getReserveExpenseAccount();
        $reserveFundAccount = $this->getReserveFundAccount();

        // Crear transacción
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $this->conjuntoConfigId,
            'transaction_date' => Carbon::create($year, $month)->endOfMonth()->toDateString(),
            'description' => "Apropiación mensual fondo de reserva - {$month}/{$year}",
            'reference_type' => 'reserve_fund_appropriation',
            'reference_id' => null,
            'status' => 'borrador',
            'created_by' => auth()->id() ?? 1,
        ]);

        // Débito: Gasto por apropiación de reserva
        $transaction->addEntry([
            'account_id' => $expenseAccount->id,
            'description' => "Apropiación fondo de reserva {$month}/{$year} (30% ingresos operacionales)",
            'debit_amount' => $amount,
            'credit_amount' => 0,
        ]);

        // Crédito: Fondo de Reserva (incrementa el patrimonio)
        $transaction->addEntry([
            'account_id' => $reserveFundAccount->id,
            'description' => "Incremento fondo de reserva {$month}/{$year}",
            'debit_amount' => 0,
            'credit_amount' => $amount,
        ]);

        // Contabilizar automáticamente
        $transaction->post();

        return $transaction;
    }

    /**
     * Obtiene la cuenta de gasto para apropiación de reserva
     * Si no existe, la sugiere crear
     */
    private function getReserveExpenseAccount(): ChartOfAccounts
    {
        $account = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', '530502')
            ->first();

        if (! $account) {
            // Si no existe la cuenta, crear sugerencia o lanzar excepción con información
            throw new \Exception(
                "No se encontró la cuenta 530502 'Apropiación Fondo de Reserva'. ".
                'Esta cuenta debe ser creada en el plan de cuentas para cumplir con la Ley 675.'
            );
        }

        return $account;
    }

    /**
     * Obtiene la cuenta del fondo de reserva
     */
    private function getReserveFundAccount(): ChartOfAccounts
    {
        $account = ChartOfAccounts::forConjunto($this->conjuntoConfigId)
            ->where('code', '320501')
            ->first();

        if (! $account) {
            throw new \Exception(
                "No se encontró la cuenta 320501 'Fondo de Reserva (Ley 675)'. ".
                'Esta cuenta es obligatoria según la normativa de propiedad horizontal.'
            );
        }

        return $account;
    }
}
