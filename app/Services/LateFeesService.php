<?php

namespace App\Services;

use App\Events\LateFeeApplied;
use App\Models\Invoice;
use App\Settings\PaymentSettings;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LateFeesService
{
    private PaymentSettings $paymentSettings;

    public function __construct(PaymentSettings $paymentSettings)
    {
        $this->paymentSettings = $paymentSettings;
    }

    /**
     * Procesa la mora mensual para una factura vencida
     */
    public function processMonthlyLateFee(Invoice $invoice, Carbon $processDate, bool $isDryRun = false): array
    {
        // Verificar si ya se procesó la mora para este mes
        if ($this->hasLateFeeForCurrentMonth($invoice, $processDate)) {
            return [
                'applied' => false,
                'reason' => 'Ya se procesó la mora para este mes',
                'late_fee_amount' => 0,
            ];
        }

        // Verificar si la factura está vencida
        if (! $invoice->isOverdue()) {
            return [
                'applied' => false,
                'reason' => 'La factura no está vencida',
                'late_fee_amount' => 0,
            ];
        }

        // Verificar si tiene balance pendiente
        if ($invoice->balance_amount <= 0) {
            return [
                'applied' => false,
                'reason' => 'No hay balance pendiente',
                'late_fee_amount' => 0,
            ];
        }

        // Verificar período de gracia
        $gracePeriodEnd = $invoice->due_date->copy()->addDays($this->paymentSettings->grace_period_days);
        if ($processDate->lte($gracePeriodEnd)) {
            return [
                'applied' => false,
                'reason' => 'Dentro del período de gracia',
                'late_fee_amount' => 0,
            ];
        }

        // Calcular la mora para este mes
        $lateFeeAmount = $this->calculateMonthlyLateFee($invoice, $processDate);

        if ($lateFeeAmount <= 0) {
            return [
                'applied' => false,
                'reason' => 'Monto de mora calculado es cero',
                'late_fee_amount' => 0,
            ];
        }

        if (! $isDryRun) {
            // Aplicar la mora
            $this->applyLateFeeToInvoice($invoice, $lateFeeAmount, $processDate);
        }

        return [
            'applied' => true,
            'reason' => 'Mora aplicada exitosamente',
            'late_fee_amount' => $lateFeeAmount,
        ];
    }

    /**
     * Calcula el monto de mora mensual
     *
     * IMPORTANTE: La mora se calcula SOLO sobre el capital adeudado (saldo sin moras previas).
     * No se permite cobrar intereses sobre intereses (anatocismo).
     */
    private function calculateMonthlyLateFee(Invoice $invoice, Carbon $processDate): float
    {
        if (! $this->paymentSettings->late_fees_enabled) {
            return 0;
        }

        // Calcular el balance de capital (excluyendo moras previas)
        // Este método asegura que NO se calculen intereses sobre intereses
        $capitalBalance = $invoice->getCapitalBalance();

        // Si no hay balance de capital, no hay mora
        if ($capitalBalance <= 0) {
            return 0;
        }

        $monthlyRate = $this->paymentSettings->late_fee_percentage / 100;
        $lateFeeAmount = $capitalBalance * $monthlyRate;

        return round($lateFeeAmount, 2);
    }

    /**
     * Aplica la mora a la factura
     */
    private function applyLateFeeToInvoice(Invoice $invoice, float $lateFeeAmount, Carbon $processDate): void
    {
        DB::transaction(function () use ($invoice, $lateFeeAmount, $processDate) {
            // Guardar el monto base original si no existe
            if (is_null($invoice->original_base_amount)) {
                $invoice->original_base_amount = $invoice->subtotal;
            }

            // Actualizar el campo late_fees con el nuevo monto
            $invoice->late_fees += $lateFeeAmount;

            // Actualizar el historial de moras
            $lateFeesHistory = $invoice->late_fee_history ?? [];
            $lateFeesHistory[] = [
                'date' => $processDate->format('Y-m-d'),
                'amount' => $lateFeeAmount,
                'base_amount' => $invoice->getCapitalBalance(), // Usar balance de capital, no balance total
                'balance_amount' => $invoice->balance_amount, // Guardar también el balance total para referencia
                'rate' => $this->paymentSettings->late_fee_percentage,
                'month' => $processDate->format('Y-m'),
            ];
            $invoice->late_fee_history = $lateFeesHistory;

            // Actualizar contadores
            $invoice->late_fee_months_applied += 1;
            $invoice->last_late_fee_calculation_date = $processDate->toDateString();

            // Recalcular totales
            $invoice->calculateTotals();

            // Actualizar status
            $invoice->updateStatus();

            Log::info('Mora aplicada', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'apartment' => $invoice->apartment->number,
                'amount' => $lateFeeAmount,
                'process_date' => $processDate->format('Y-m-d'),
                'month' => $processDate->format('Y-m'),
            ]);

            // Disparar evento para generar asiento contable independiente
            event(new LateFeeApplied($invoice, $lateFeeAmount, $processDate));
        });
    }

    /**
     * Verifica si ya se procesó la mora para el mes actual
     */
    private function hasLateFeeForCurrentMonth(Invoice $invoice, Carbon $processDate): bool
    {
        if (is_null($invoice->last_late_fee_calculation_date)) {
            return false;
        }

        $lastProcessDate = Carbon::parse($invoice->last_late_fee_calculation_date);

        // Si se procesó en el mismo mes y año, ya está procesado
        return $lastProcessDate->isSameMonth($processDate);
    }

    /**
     * Obtiene las facturas que necesitan procesamiento de mora
     */
    public function getInvoicesNeedingLateFeeProcessing(Carbon $processDate): \Illuminate\Database\Eloquent\Collection
    {
        return Invoice::overdue()
            ->whereIn('status', ['vencido', 'pago_parcial'])
            ->where('balance_amount', '>', 0)
            ->where(function ($query) use ($processDate) {
                // Facturas que nunca han tenido mora procesada
                $query->whereNull('last_late_fee_calculation_date')
                    // O facturas que no han sido procesadas este mes
                    ->orWhere('last_late_fee_calculation_date', '<', $processDate->startOfMonth());
            })
            ->get();
    }

    /**
     * Calcula el resumen de moras para un reporte
     */
    public function getLateFeesSummary(Carbon $startDate, Carbon $endDate): array
    {
        $invoicesWithLateFees = Invoice::whereNotNull('late_fee_history')
            ->where('late_fees', '>', 0)
            ->whereBetween('last_late_fee_calculation_date', [$startDate, $endDate])
            ->with('apartment')
            ->get();

        $totalLateFees = $invoicesWithLateFees->sum('late_fees');
        $affectedInvoices = $invoicesWithLateFees->count();
        $affectedApartments = $invoicesWithLateFees->pluck('apartment_id')->unique()->count();

        return [
            'total_late_fees' => $totalLateFees,
            'affected_invoices' => $affectedInvoices,
            'affected_apartments' => $affectedApartments,
            'invoices' => $invoicesWithLateFees,
        ];
    }
}
