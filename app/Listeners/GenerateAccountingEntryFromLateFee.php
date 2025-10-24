<?php

namespace App\Listeners;

use App\Events\LateFeeApplied;
use App\Models\AccountingTransaction;
use App\Models\ChartOfAccounts;
use Illuminate\Support\Facades\Log;

class GenerateAccountingEntryFromLateFee
{
    public function handle(LateFeeApplied $event): void
    {
        try {
            $invoice = $event->invoice;
            $lateFeeAmount = $event->lateFeeAmount;
            $processDate = $event->processDate;
            $month = $event->month;

            // Obtener el conjunto_config_id desde la relación del apartamento
            $conjuntoConfigId = $invoice->apartment->apartmentType->conjunto_config_id;

            // Verificar si ya existe una transacción contable para este mes de mora específico
            $existingTransaction = AccountingTransaction::where('reference_type', 'late_fee')
                ->where('reference_id', $invoice->id)
                ->whereJsonContains('metadata->month', $month)
                ->first();

            if ($existingTransaction) {
                Log::info('Transacción contable de intereses de mora ya existe para este mes', [
                    'invoice_id' => $invoice->id,
                    'month' => $month,
                    'transaction_id' => $existingTransaction->id,
                ]);

                return;
            }

            // Obtener las cuentas contables para intereses de mora
            $carteraInteresMoraAccount = $this->getAccountByCode($conjuntoConfigId, '130503'); // Cartera Intereses Mora
            $ingresoInteresMoraAccount = $this->getAccountByCode($conjuntoConfigId, '413506'); // Ingresos por Intereses de Mora

            // Crear transacción contable específica para los intereses de mora de este mes
            $transaction = AccountingTransaction::create([
                'conjunto_config_id' => $conjuntoConfigId,
                'transaction_date' => $processDate,
                'description' => "Apto {$invoice->apartment->number} - Intereses de Mora - Mes {$processDate->format('m/Y')} - Factura {$invoice->invoice_number}",
                'reference_type' => 'late_fee',
                'reference_id' => $invoice->id,
                'total_debit' => 0,
                'total_credit' => 0,
                'status' => 'borrador',
                'created_by' => auth()->id(),
                'metadata' => [
                    'month' => $month,
                    'invoice_number' => $invoice->invoice_number,
                    'apartment_number' => $invoice->apartment->number,
                    'late_fee_amount' => $lateFeeAmount,
                    'process_date' => $processDate->format('Y-m-d'),
                ],
            ]);

            // Débito: Cartera Intereses de Mora (130503)
            $transaction->addEntry([
                'account_id' => $carteraInteresMoraAccount->id,
                'description' => "Intereses de mora - Apto {$invoice->apartment->number} - Mes {$processDate->format('m/Y')}",
                'debit_amount' => $lateFeeAmount,
                'credit_amount' => 0,
                'third_party_type' => 'apartment',
                'third_party_id' => $invoice->apartment_id,
            ]);

            // Crédito: Ingresos por Intereses de Mora (413506)
            $transaction->addEntry([
                'account_id' => $ingresoInteresMoraAccount->id,
                'description' => "Intereses de mora - Apto {$invoice->apartment->number} - Mes {$processDate->format('m/Y')}",
                'debit_amount' => 0,
                'credit_amount' => $lateFeeAmount,
            ]);

            // Contabilizar automáticamente
            $transaction->post();

            Log::info('Asiento contable de intereses de mora generado automáticamente', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'apartment' => $invoice->apartment->number,
                'late_fee_amount' => $lateFeeAmount,
                'month' => $month,
                'transaction_id' => $transaction->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Error generando asiento contable de intereses de mora', [
                'invoice_id' => $event->invoice->id,
                'late_fee_amount' => $event->lateFeeAmount,
                'month' => $event->month,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-lanzar la excepción para que Laravel maneje el retry
            throw $e;
        }
    }

    private function getAccountByCode(int $conjuntoConfigId, string $code): ChartOfAccounts
    {
        $account = ChartOfAccounts::forConjunto($conjuntoConfigId)
            ->where('code', $code)
            ->first();

        if (! $account) {
            throw new \Exception("No se encontró la cuenta contable con código: {$code}");
        }

        return $account;
    }
}
