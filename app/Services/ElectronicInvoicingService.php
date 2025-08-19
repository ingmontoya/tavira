<?php

namespace App\Services;

use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\Resident;

class ElectronicInvoicingService
{
    private FactusApiService $factusService;

    public function __construct(FactusApiService $factusService)
    {
        $this->factusService = $factusService;
    }

    /**
     * Determine if an invoice should be electronic based on conjunto rules and resident preferences
     */
    public function shouldUseElectronicInvoicing(Invoice $invoice): bool
    {
        $conjunto = $this->getConjuntoFromInvoice($invoice);

        if (! $conjunto || ! $conjunto->dian_electronic_invoicing_enabled) {
            return false;
        }

        return match ($conjunto->dian_electronic_invoicing_mode) {
            'disabled' => false,
            'all' => true,
            'optional' => $this->checkResidentPreference($invoice),
            'required_amount' => $this->checkAmountRequirement($invoice, $conjunto),
            default => false,
        };
    }

    /**
     * Get resident's electronic invoicing preference
     */
    private function checkResidentPreference(Invoice $invoice): bool
    {
        $resident = $this->getPrimaryResidentFromInvoice($invoice);

        if (! $resident || ! $resident->electronic_invoicing_preference) {
            // Default to physical if no preference is set
            return false;
        }

        return $resident->electronic_invoicing_preference === 'electronic';
    }

    /**
     * Check if invoice amount meets minimum requirement for electronic invoicing
     */
    private function checkAmountRequirement(Invoice $invoice, ConjuntoConfig $conjunto): bool
    {
        if (! $conjunto->dian_electronic_invoicing_min_amount) {
            return false;
        }

        return $invoice->total_amount >= $conjunto->dian_electronic_invoicing_min_amount;
    }

    /**
     * Send invoice to electronic invoicing provider
     */
    public function sendElectronicInvoice(Invoice $invoice): array
    {
        $conjunto = $this->getConjuntoFromInvoice($invoice);

        if (! $conjunto || ! $this->shouldUseElectronicInvoicing($invoice)) {
            return [
                'success' => false,
                'message' => 'La factura no debe ser enviada electrónicamente',
            ];
        }

        // Configure Factus service with conjunto's technical config
        $this->configureFactusService($conjunto);

        // Prepare invoice data for Factus
        $invoiceData = $this->prepareInvoiceDataForFactus($invoice);

        // Send to Factus
        return $this->factusService->sendInvoice($invoiceData);
    }

    /**
     * Configure Factus service with conjunto's DIAN technical configuration
     */
    private function configureFactusService(ConjuntoConfig $conjunto): void
    {
        $technicalConfig = $conjunto->dian_technical_config ?: [];

        $this->factusService->setConfig([
            'base_url' => $technicalConfig['factus_base_url'] ?? 'https://api-sandbox.factus.com.co',
            'email' => $technicalConfig['factus_email'] ?? '',
            'password' => $technicalConfig['factus_password'] ?? '',
            'client_id' => $technicalConfig['factus_client_id'] ?? '',
            'client_secret' => $technicalConfig['factus_client_secret'] ?? '',
        ]);
    }

    /**
     * Prepare invoice data in Factus format according to API documentation
     */
    private function prepareInvoiceDataForFactus(Invoice $invoice): array
    {
        $apartment = $invoice->apartment;
        $resident = $this->getPrimaryResidentFromInvoice($invoice);
        $conjunto = $this->getConjuntoFromInvoice($invoice);
        $companyInfo = $conjunto->dian_company_info ?? [];
        $technicalConfig = $conjunto->dian_technical_config ?? [];

        // Get the first numbering range or use a default one
        $numberingRanges = $conjunto->dian_numbering_ranges ?? [];
        $numberingRangeId = ! empty($numberingRanges) ? ($numberingRanges[0]['id'] ?? 1) : 1;

        return [
            // Required basic fields - Use null for sandbox testing as IDs may not exist
            'numbering_range_id' => null, // Will be assigned by Factus in sandbox
            'reference_code' => $invoice->invoice_number,
            'document' => '01', // Factura de venta
            'observation' => $invoice->dian_observation ?: "Factura de administración - Apartamento {$apartment->number}",

            // Date information
            'issued_at' => $invoice->billing_date->format('Y-m-d H:i:s'),
            'due_at' => $invoice->due_date->format('Y-m-d H:i:s'),

            // Customer information - Use resident DIAN fields when available
            'customer' => [
                'identification_document_id' => 1, // Default document type ID for Cédula
                'identification' => $resident->document_number ?? '12345678',
                'names' => $resident->full_name ?? 'Cliente Genérico',
                'email' => $resident->email ?? 'cliente@example.com',
                'phone' => $resident->phone ?? $resident->mobile_phone ?? '3001234567',
                'address' => $resident->dian_address ?: ($apartment ? "Apartamento {$apartment->number}, Torre {$apartment->tower}" : 'Dirección no disponible'),
                'city_id' => $resident->dian_city_id ?: 1, // Default city ID (usually Bogotá in sandbox)
                'legal_organization_type' => $resident->dian_legal_organization_type ?: 1, // Persona natural
                'tributary_regime' => $resident->dian_tributary_regime ?: 1, // Régimen simplificado
                'tributary_liability' => $resident->dian_tributary_liability ?: 1, // No responsable
            ],

            // Invoice items - Corrected structure
            'items' => $this->prepareFactusInvoiceItems($invoice),

            // Payment terms
            'payment_method' => $invoice->dian_payment_method ?: 1, // Default payment method
            'payment_due_date' => $invoice->due_date->format('Y-m-d'),

            // Currency
            'currency' => $invoice->dian_currency ?: 'COP',

            // Totals
            'subtotal' => (float) $invoice->subtotal,
            'total_discount' => (float) $invoice->early_discount,
            'total_tax' => 0.00, // No tax typically for administration fees
            'total' => (float) $invoice->total_amount,
        ];
    }

    /**
     * Prepare invoice items for Factus in the correct format
     */
    private function prepareFactusInvoiceItems(Invoice $invoice): array
    {
        $items = [];
        $conjunto = $this->getConjuntoFromInvoice($invoice);

        // Get measurement unit from DIAN configuration or use default
        $measurementUnits = $conjunto->dian_measurement_units ?? [];
        $defaultMeasurementUnit = ! empty($measurementUnits) ? $measurementUnits[0]['code'] ?? 'NIU' : 'NIU';

        foreach ($invoice->items as $item) {
            $items[] = [
                'code_reference' => 'ADM001', // Default product code for administration
                'name' => $item->description,
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'price' => (float) $item->unit_price,
                'total' => (float) $item->total_price,
                'discount_rate' => 0.00,
                'discount_amount' => 0.00,
                'is_excluded' => 0, // Not excluded from tax (integer)
                'unit_measure_id' => 70, // NIU - Número de Items (unidad común para servicios)
                'standard_code_id' => 1, // Default standard code
                'tribute_id' => 1, // Default tax ID (usually IVA 0%)
                'tax_rate' => 0.00, // No tax for administration fees typically
                'tax_amount' => 0.00,
            ];
        }

        return $items;
    }

    /**
     * Map document types to Factus format
     */
    private function mapDocumentType(string $documentType): string
    {
        return match (strtoupper($documentType)) {
            'CC' => '13', // Cédula de ciudadanía
            'CE' => '22', // Cédula de extranjería
            'NIT' => '31', // NIT
            'PASSPORT' => '41', // Pasaporte
            default => '13', // Default to CC
        };
    }

    /**
     * Get city code from conjunto configuration
     */
    private function getCityCode(ConjuntoConfig $conjunto): string
    {
        $municipalities = $conjunto->dian_municipalities ?? [];

        if (! empty($municipalities)) {
            return $municipalities[0]['code'] ?? '11001'; // Default to Bogotá
        }

        return '11001'; // Default to Bogotá
    }

    /**
     * Get department code from city code
     */
    private function getDepartmentCode(ConjuntoConfig $conjunto): string
    {
        $cityCode = $this->getCityCode($conjunto);

        // Extract department code from city code (first 2 digits)
        return substr($cityCode, 0, 2);
    }

    /**
     * Get conjunto configuration from invoice
     */
    private function getConjuntoFromInvoice(Invoice $invoice): ?ConjuntoConfig
    {
        return $invoice->apartment?->conjuntoConfig ?? ConjuntoConfig::where('is_active', true)->first();
    }

    /**
     * Get primary resident (owner) from invoice apartment
     */
    private function getPrimaryResidentFromInvoice(Invoice $invoice): ?Resident
    {
        if (! $invoice->apartment) {
            return null;
        }

        // Get the owner first, or any active resident if no owner
        return $invoice->apartment->residents()
            ->active()
            ->owners()
            ->first()
            ?? $invoice->apartment->residents()
                ->active()
                ->first();
    }

    /**
     * Get invoice status from electronic invoicing provider
     */
    public function getElectronicInvoiceStatus(Invoice $invoice): array
    {
        $conjunto = $this->getConjuntoFromInvoice($invoice);

        if (! $conjunto) {
            return [
                'success' => false,
                'message' => 'No se encontró configuración del conjunto',
            ];
        }

        $this->configureFactusService($conjunto);

        return $this->factusService->getInvoiceStatus($invoice->invoice_number);
    }
}
