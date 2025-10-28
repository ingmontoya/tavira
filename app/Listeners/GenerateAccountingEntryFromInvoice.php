<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Models\AccountingTransaction;
use App\Models\ChartOfAccounts;
use Illuminate\Support\Facades\Log;

class GenerateAccountingEntryFromInvoice
{
    public function handle(InvoiceCreated $event): void
    {
        try {
            $invoice = $event->invoice;

            // Verificar si ya existen transacciones contables para esta factura
            $existingTransactions = AccountingTransaction::where('reference_type', 'invoice')
                ->where('reference_id', $invoice->id)
                ->count();

            if ($existingTransactions > 0) {
                Log::info('Transacciones contables ya existen para la factura', [
                    'invoice_id' => $invoice->id,
                    'existing_transactions' => $existingTransactions,
                ]);

                return;
            }

            // Obtener el conjunto_config_id desde la relación del apartamento
            $conjuntoConfigId = $invoice->apartment->apartmentType->conjunto_config_id;

            // Cargar los items de la factura con sus conceptos
            $invoice->load('items.paymentConcept');

            // Si la factura no tiene items, usar el método anterior (compatibilidad)
            if ($invoice->items->isEmpty()) {
                $this->createSingleTransaction($invoice, $conjuntoConfigId);

                return;
            }

            // Crear una transacción por cada concepto diferente
            $itemsByConceptType = $invoice->items->groupBy(function ($item) {
                return $item->paymentConcept ? $item->paymentConcept->type : 'common_expense';
            });

            foreach ($itemsByConceptType as $conceptType => $items) {
                $this->createTransactionForConcept($invoice, $conjuntoConfigId, $conceptType, $items);
            }

            Log::info('Asientos contables generados automáticamente por concepto', [
                'invoice_id' => $invoice->id,
                'total_amount' => $invoice->total_amount,
                'concepts_count' => $itemsByConceptType->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error generando asiento contable desde factura', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            // Re-lanzar la excepción para que Laravel maneje el retry
            throw $e;
        }
    }

    private function createTransactionForConcept($invoice, $conjuntoConfigId, $conceptType, $items)
    {
        $totalAmount = $items->sum('total_price');
        $firstItem = $items->first();

        // Obtener las cuentas contables según el concepto
        $accounts = $this->getAccountsForConceptType($conjuntoConfigId, $conceptType, $firstItem->payment_concept_id ?? null);

        if (! $accounts) {
            Log::warning('No se encontraron cuentas para el concepto', [
                'concept_type' => $conceptType,
                'invoice_id' => $invoice->id,
            ]);

            return;
        }

        // Crear transacción contable específica para este concepto
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $conjuntoConfigId,
            'transaction_date' => $invoice->billing_date,
            'description' => "Apto {$invoice->apartment->number} - Factura {$invoice->invoice_number} - {$this->getConceptTypeLabel($conceptType)}",
            'reference_type' => 'invoice',
            'reference_id' => $invoice->id,
            'total_debit' => 0,
            'total_credit' => 0,
            'status' => 'borrador',
            'created_by' => auth()->id(),
        ]);

        // Débito: Cartera por concepto específico
        $transaction->addEntry([
            'account_id' => $accounts['receivable_account']->id,
            'description' => "Cartera {$this->getConceptTypeLabel($conceptType)} - {$invoice->invoice_number}",
            'debit_amount' => $totalAmount,
            'credit_amount' => 0,
            'third_party_type' => 'apartment',
            'third_party_id' => $invoice->apartment_id,
        ]);

        // Crédito: Ingreso por concepto específico
        $transaction->addEntry([
            'account_id' => $accounts['income_account']->id,
            'description' => "Ingreso {$this->getConceptTypeLabel($conceptType)} - {$invoice->invoice_number}",
            'debit_amount' => 0,
            'credit_amount' => $totalAmount,
        ]);

        // Contabilizar automáticamente
        $transaction->post();
    }

    private function createSingleTransaction($invoice, $conjuntoConfigId)
    {
        // Método anterior para compatibilidad
        $transaction = AccountingTransaction::create([
            'conjunto_config_id' => $conjuntoConfigId,
            'transaction_date' => $invoice->billing_date,
            'description' => "Apto {$invoice->apartment->number} - Factura {$invoice->invoice_number}",
            'reference_type' => 'invoice',
            'reference_id' => $invoice->id,
            'total_debit' => 0,
            'total_credit' => 0,
            'status' => 'borrador',
            'created_by' => auth()->id(),
        ]);

        $carteraAccount = $this->getAccountByCode($conjuntoConfigId, '13050505');
        $ingresoAccount = $this->getAccountByCode($conjuntoConfigId, '417005');

        $transaction->addEntry([
            'account_id' => $carteraAccount->id,
            'description' => "Cartera factura {$invoice->invoice_number}",
            'debit_amount' => $invoice->total_amount,
            'credit_amount' => 0,
            'third_party_type' => 'apartment',
            'third_party_id' => $invoice->apartment_id,
        ]);

        $transaction->addEntry([
            'account_id' => $ingresoAccount->id,
            'description' => "Ingreso por administración {$invoice->invoice_number}",
            'debit_amount' => 0,
            'credit_amount' => $invoice->total_amount,
        ]);

        $transaction->post();
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

        // Mapeo por defecto basado en el tipo (usando códigos PUC correctos del seeder)
        $defaultMappings = [
            'monthly_administration' => [
                'income_code' => '417005', // Cuotas de Administración
                'receivable_code' => '13050505', // Cartera Administración
            ],
            'common_expense' => [
                'income_code' => '417005', // Cuotas de Administración
                'receivable_code' => '13050505', // Cartera Administración
            ],
            'sanction' => [
                'income_code' => '417005', // Cuotas de Administración (Multas se incluyen aquí)
                'receivable_code' => '13050525', // Sanciones Asamblea
            ],
            'parking' => [
                'income_code' => '417005', // Cuotas de Administración (incluye parqueaderos)
                'receivable_code' => '13050530', // Uso Zonas Comunes
            ],
            'late_fee' => [
                'income_code' => '417010', // Intereses de Mora Cuotas de Administración
                'receivable_code' => '13050510', // Intereses de Mora Cuotas de Administración
            ],
            'special' => [
                'income_code' => '417015', // Cuota Extra para Fachadas
                'receivable_code' => '13050515', // Cuota Extra para Fachadas
            ],
        ];

        $mapping = $defaultMappings[$conceptType] ?? $defaultMappings['common_expense'];

        return [
            'income_account' => $this->getAccountByCode($conjuntoConfigId, $mapping['income_code']),
            'receivable_account' => $this->getAccountByCode($conjuntoConfigId, $mapping['receivable_code']),
        ];
    }

    private function getConceptTypeLabel($conceptType)
    {
        $labels = [
            'monthly_administration' => 'Administración Mensual',
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
