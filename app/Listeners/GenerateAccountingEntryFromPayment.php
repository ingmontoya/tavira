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
            $paymentMethod = $event->paymentMethod ?? 'bank_transfer';

            // Obtener el conjunto_config_id desde la relación del apartamento
            $conjuntoConfigId = $invoice->apartment->apartmentType->conjunto_config_id;

            // Cargar los items de la factura con sus conceptos
            $invoice->load('items.paymentConcept');

            // Si la factura no tiene items, usar el método anterior
            if ($invoice->items->isEmpty()) {
                $this->createSinglePaymentTransaction($invoice, $conjuntoConfigId, $paymentAmount, $paymentMethod);

                return;
            }

            // Distribuir el pago proporcionalmente entre los conceptos
            $itemsByConceptType = $invoice->items->groupBy(function ($item) {
                return $item->paymentConcept ? $item->paymentConcept->type : 'common_expense';
            });

            foreach ($itemsByConceptType as $conceptType => $items) {
                $conceptTotal = $items->sum('total_price');
                $conceptPayment = ($paymentAmount * $conceptTotal) / $invoice->total_amount;

                if ($conceptPayment > 0) {
                    $this->createPaymentTransactionForConcept(
                        $invoice,
                        $conjuntoConfigId,
                        $conceptType,
                        $conceptPayment,
                        $paymentMethod,
                        $items
                    );
                }
            }

            Log::info('Asientos contables de pago generados por concepto', [
                'invoice_id' => $invoice->id,
                'payment_amount' => $paymentAmount,
                'payment_method' => $paymentMethod,
                'concepts_count' => $itemsByConceptType->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error generando asiento contable desde pago', [
                'invoice_id' => $invoice->id,
                'payment_amount' => $paymentAmount,
                'error' => $e->getMessage(),
            ]);

            // Re-lanzar la excepción para que Laravel maneje el retry
            throw $e;
        }
    }

    private function createPaymentTransactionForConcept($invoice, $conjuntoConfigId, $conceptType, $paymentAmount, $paymentMethod, $items)
    {
        $firstItem = $items->first();

        // Obtener las cuentas contables según el concepto
        $accounts = $this->getAccountsForConceptType($conjuntoConfigId, $conceptType, $firstItem->payment_concept_id ?? null);

        if (! $accounts) {
            Log::warning('No se encontraron cuentas para el pago del concepto', [
                'concept_type' => $conceptType,
                'invoice_id' => $invoice->id,
            ]);

            return;
        }

        // Determinar cuenta de efectivo según método de pago
        $cashAccount = $this->getCashAccountForPaymentMethod($conjuntoConfigId, $paymentMethod);

        // Crear transacción contable específica para este concepto
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $conjuntoConfigId,
            'transaction_date' => now()->toDateString(),
            'description' => "Pago {$this->getConceptTypeLabel($conceptType)} - Factura {$invoice->invoice_number} - Apto {$invoice->apartment->number}",
            'reference_type' => 'payment',
            'reference_id' => $invoice->id,
            'created_by' => auth()->id(),
        ]);

        // Débito: Cuenta de efectivo (ingreso de dinero)
        $transaction->addEntry([
            'account_id' => $cashAccount->id,
            'description' => "Pago recibido {$this->getConceptTypeLabel($conceptType)} - {$invoice->invoice_number}",
            'debit_amount' => $paymentAmount,
            'credit_amount' => 0,
        ]);

        // Crédito: Cartera específica del concepto (disminución de cartera)
        $transaction->addEntry([
            'account_id' => $accounts['receivable_account']->id,
            'description' => "Pago cartera {$this->getConceptTypeLabel($conceptType)} - {$invoice->invoice_number}",
            'debit_amount' => 0,
            'credit_amount' => $paymentAmount,
            'third_party_type' => 'apartment',
            'third_party_id' => $invoice->apartment_id,
        ]);

        // Contabilizar automáticamente
        $transaction->post();
    }

    private function createSinglePaymentTransaction($invoice, $conjuntoConfigId, $paymentAmount, $paymentMethod)
    {
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $conjuntoConfigId,
            'transaction_date' => now()->toDateString(),
            'description' => "Pago factura {$invoice->invoice_number} - Apto {$invoice->apartment->number}",
            'reference_type' => 'payment',
            'reference_id' => $invoice->id,
            'created_by' => auth()->id(),
        ]);

        $cashAccount = $this->getCashAccountForPaymentMethod($conjuntoConfigId, $paymentMethod);
        $carteraAccount = $this->getAccountByCode($conjuntoConfigId, '130501');

        $transaction->addEntry([
            'account_id' => $cashAccount->id,
            'description' => "Pago recibido factura {$invoice->invoice_number}",
            'debit_amount' => $paymentAmount,
            'credit_amount' => 0,
        ]);

        $transaction->addEntry([
            'account_id' => $carteraAccount->id,
            'description' => "Pago cartera factura {$invoice->invoice_number}",
            'debit_amount' => 0,
            'credit_amount' => $paymentAmount,
            'third_party_type' => 'apartment',
            'third_party_id' => $invoice->apartment_id,
        ]);

        $transaction->post();
    }

    private function getCashAccountForPaymentMethod($conjuntoConfigId, $paymentMethod)
    {
        $account = \App\Models\PaymentMethodAccountMapping::getCashAccountForPaymentMethod($conjuntoConfigId, $paymentMethod);

        if (! $account) {
            throw new \Exception("No se encontró mapeo de cuenta para el método de pago: {$paymentMethod}");
        }

        return $account;
    }

    private function getAccountsForConceptType($conjuntoConfigId, $conceptType, $paymentConceptId = null)
    {
        // Si tenemos el ID del concepto, usar el mapeo específico
        if ($paymentConceptId) {
            $accounts = \App\Models\PaymentConceptAccountMapping::getAccountsForConcept($paymentConceptId);
            if ($accounts) {
                return $accounts;
            }
        }

        // Mapeo por defecto basado en el tipo
        $defaultMappings = [
            'common_expense' => [
                'receivable_code' => '130501', // Cartera Administración
            ],
            'sanction' => [
                'receivable_code' => '130501', // Cartera Administración
            ],
            'parking' => [
                'receivable_code' => '130501', // Cartera Administración
            ],
            'late_fee' => [
                'receivable_code' => '130503', // Cartera Intereses Mora
            ],
            'special' => [
                'receivable_code' => '130502', // Cartera Cuotas Extraordinarias
            ],
        ];

        $mapping = $defaultMappings[$conceptType] ?? $defaultMappings['common_expense'];

        return [
            'receivable_account' => $this->getAccountByCode($conjuntoConfigId, $mapping['receivable_code']),
        ];
    }

    private function getConceptTypeLabel($conceptType)
    {
        $labels = [
            'common_expense' => 'Cuotas Administración',
            'sanction' => 'Multas y Sanciones',
            'parking' => 'Parqueaderos',
            'late_fee' => 'Intereses de Mora',
            'special' => 'Cuotas Extraordinarias',
        ];

        return $labels[$conceptType] ?? 'Otros Ingresos';
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
