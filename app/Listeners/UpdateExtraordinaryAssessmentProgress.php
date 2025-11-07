<?php

namespace App\Listeners;

use App\Events\PaymentReceived;
use App\Models\ExtraordinaryAssessmentApartment;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

class UpdateExtraordinaryAssessmentProgress implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(PaymentReceived $event): void
    {
        try {
            $invoice = $event->invoice;
            $paymentAmount = $event->paymentAmount;

            // Cargar los ítems de la factura con sus conceptos
            $invoice->load('items.paymentConcept');

            // Filtrar los ítems que son de cuotas extraordinarias
            $extraordinaryItems = $invoice->items->filter(function ($item) {
                return $item->paymentConcept &&
                    $item->paymentConcept->type === 'extraordinary_assessment';
            });

            if ($extraordinaryItems->isEmpty()) {
                // No hay ítems de extraordinarias en esta factura
                return;
            }

            // Calcular cuánto del pago total se aplicó a extraordinarias
            $extraordinaryTotal = $extraordinaryItems->sum('total_price');
            $extraordinaryPayment = ($paymentAmount * $extraordinaryTotal) / $invoice->total_amount;

            if ($extraordinaryPayment <= 0) {
                return;
            }

            // Agrupar los ítems por extraordinaria (basado en el nombre en la descripción)
            // Nota: Esto es una aproximación. Idealmente deberíamos tener una relación directa
            $this->distributePaymentToAssessments(
                $invoice->apartment_id,
                $extraordinaryPayment,
                $extraordinaryItems
            );

            Log::info('Progreso de cuotas extraordinarias actualizado', [
                'invoice_id' => $invoice->id,
                'apartment_id' => $invoice->apartment_id,
                'extraordinary_payment' => $extraordinaryPayment,
                'extraordinary_items_count' => $extraordinaryItems->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Error actualizando progreso de cuota extraordinaria', [
                'invoice_id' => $event->invoice->id,
                'payment_amount' => $event->paymentAmount,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-lanzar la excepción para que Laravel maneje el retry
            throw $e;
        }
    }

    /**
     * Distribuye el pago entre las cuotas extraordinarias del apartamento
     */
    private function distributePaymentToAssessments(int $apartmentId, float $totalPayment, $items): void
    {
        // Si solo hay un ítem, es más simple
        if ($items->count() === 1) {
            $this->applyPaymentToAssessment($apartmentId, $totalPayment, $items->first());

            return;
        }

        // Si hay múltiples ítems, distribuir proporcionalmente
        $totalExtraordinary = $items->sum('total_price');

        foreach ($items as $item) {
            $itemProportion = $item->total_price / $totalExtraordinary;
            $itemPayment = $totalPayment * $itemProportion;

            $this->applyPaymentToAssessment($apartmentId, $itemPayment, $item);
        }
    }

    /**
     * Aplica un pago a una cuota extraordinaria específica
     */
    private function applyPaymentToAssessment(int $apartmentId, float $payment, $item): void
    {
        // Buscar la cuota extraordinaria activa para este apartamento
        // Dado que estamos generando facturas mes a mes, deberíamos tener solo una activa
        $assessmentApartments = ExtraordinaryAssessmentApartment::where('apartment_id', $apartmentId)
            ->whereHas('extraordinaryAssessment', function ($query) {
                $query->where('status', 'active');
            })
            ->where('status', '!=', 'completed')
            ->get();

        if ($assessmentApartments->isEmpty()) {
            Log::warning('No se encontró cuota extraordinaria activa para el apartamento', [
                'apartment_id' => $apartmentId,
                'item_description' => $item->description,
            ]);

            return;
        }

        // Si hay solo una, aplicar el pago ahí
        if ($assessmentApartments->count() === 1) {
            $assessmentApartments->first()->registerPayment($payment);

            return;
        }

        // Si hay múltiples, intentar identificar por la descripción del ítem
        // La descripción tiene el formato: "Cuota Extraordinaria - {nombre} (Cuota X/Y)"
        foreach ($assessmentApartments as $assessmentApartment) {
            $extraordinaryName = $assessmentApartment->extraordinaryAssessment->name;

            if (str_contains($item->description, $extraordinaryName)) {
                $assessmentApartment->registerPayment($payment);

                return;
            }
        }

        // Si no se pudo identificar, aplicar al primero pendiente
        $assessmentApartments->first()->registerPayment($payment);

        Log::warning('No se pudo identificar la extraordinaria por descripción, se aplicó a la primera pendiente', [
            'apartment_id' => $apartmentId,
            'item_description' => $item->description,
            'assessment_id' => $assessmentApartments->first()->extraordinary_assessment_id,
        ]);
    }
}
