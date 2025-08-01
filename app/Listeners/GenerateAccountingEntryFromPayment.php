<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Models\AccountingTransaction;
use App\Models\ChartOfAccounts;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class GenerateAccountingEntryFromPayment implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(PaymentReceived $event): void
    {
        try {
            $invoice = $event->invoice;
            $paymentAmount = $event->paymentAmount;
            
            // Obtener el conjunto_config_id desde la relación del apartamento
            $conjuntoConfigId = $invoice->apartment->apartmentType->conjunto_config_id;

            // Crear transacción contable para el pago
            $transaction = AccountingTransaction::create([
                'conjunto_config_id' => $conjuntoConfigId,
                'transaction_date' => now()->toDateString(),
                'description' => "Pago factura {$invoice->invoice_number} - Apto {$invoice->apartment->number}",
                'reference_type' => 'payment',
                'reference_id' => $invoice->id,
                'created_by' => auth()->id() ?? 1,
            ]);

            // Obtener cuentas contables
            $bancoAccount = $this->getAccountByCode($conjuntoConfigId, '111001'); // Banco Principal
            $carteraAccount = $this->getAccountByCode($conjuntoConfigId, '130501'); // Cartera Administración

            // Débito: Banco/Caja (ingreso de dinero)
            $transaction->addEntry([
                'account_id' => $bancoAccount->id,
                'description' => "Pago recibido factura {$invoice->invoice_number}",
                'debit_amount' => $paymentAmount,
                'credit_amount' => 0,
            ]);

            // Crédito: Cartera de clientes (disminución de cartera)
            $transaction->addEntry([
                'account_id' => $carteraAccount->id,
                'description' => "Pago cartera factura {$invoice->invoice_number}",
                'debit_amount' => 0,
                'credit_amount' => $paymentAmount,
                'third_party_type' => 'apartment',
                'third_party_id' => $invoice->apartment_id,
            ]);

            // Contabilizar automáticamente
            $transaction->post();

            Log::info("Asiento contable de pago generado automáticamente", [
                'transaction_id' => $transaction->id,
                'invoice_id' => $invoice->id,
                'payment_amount' => $paymentAmount,
                'payment_method' => $event->paymentMethod
            ]);

        } catch (\Exception $e) {
            Log::error("Error generando asiento contable desde pago", [
                'invoice_id' => $invoice->id,
                'payment_amount' => $paymentAmount,
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
