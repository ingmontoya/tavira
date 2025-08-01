<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Models\AccountingTransaction;
use App\Models\ChartOfAccounts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class GenerateAccountingEntryFromInvoice implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(InvoiceCreated $event): void
    {
        try {
            $invoice = $event->invoice;
            
            // Obtener el conjunto_config_id desde la relación del apartamento
            $conjuntoConfigId = $invoice->apartment->apartmentType->conjunto_config_id;

            // Crear transacción contable
            $transaction = AccountingTransaction::create([
                'conjunto_config_id' => $conjuntoConfigId,
                'transaction_date' => $invoice->billing_date,
                'description' => "Factura {$invoice->invoice_number} - Apto {$invoice->apartment->number}",
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Obtener cuentas contables
            $carteraAccount = $this->getAccountByCode($conjuntoConfigId, '130501'); // Cartera Administración
            $ingresoAccount = $this->getAccountByCode($conjuntoConfigId, '413501'); // Cuotas de Administración

            // Débito: Cartera de clientes
            $transaction->addEntry([
                'account_id' => $carteraAccount->id,
                'description' => "Cartera factura {$invoice->invoice_number}",
                'debit_amount' => $invoice->total_amount,
                'credit_amount' => 0,
                'third_party_type' => 'apartment',
                'third_party_id' => $invoice->apartment_id,
            ]);

            // Crédito: Ingresos por administración
            $transaction->addEntry([
                'account_id' => $ingresoAccount->id,
                'description' => "Ingreso por administración {$invoice->invoice_number}",
                'debit_amount' => 0,
                'credit_amount' => $invoice->total_amount,
            ]);

            // Contabilizar automáticamente
            $transaction->post();

            Log::info("Asiento contable generado automáticamente", [
                'transaction_id' => $transaction->id,
                'invoice_id' => $invoice->id,
                'amount' => $invoice->total_amount
            ]);

        } catch (\Exception $e) {
            Log::error("Error generando asiento contable desde factura", [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
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

        if (!$account) {
            throw new \Exception("No se encontró la cuenta contable con código: {$code}");
        }

        return $account;
    }
}
