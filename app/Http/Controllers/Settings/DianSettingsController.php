<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\ConjuntoConfig;
use App\Services\DianDataService;
use App\Services\FactusApiService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DianSettingsController extends Controller
{
    public function index()
    {
        $conjuntoConfig = ConjuntoConfig::where('is_active', true)->first();

        if (! $conjuntoConfig) {
            return redirect()->route('dashboard')
                ->with('error', 'No hay configuración de conjunto activa');
        }

        // Ensure we have proper boolean and array values
        $dianEnabled = $conjuntoConfig->dian_electronic_invoicing_enabled;
        if ($dianEnabled === null) {
            $dianEnabled = false;
        }

        return Inertia::render('settings/DianSettings', [
            'conjuntoConfig' => $conjuntoConfig,
            'dianConfig' => [
                'enabled' => (bool) $dianEnabled,
                'mode' => $conjuntoConfig->dian_electronic_invoicing_mode ?? 'disabled',
                'minAmount' => $conjuntoConfig->dian_electronic_invoicing_min_amount ?? 0,
                'numberingRanges' => is_array($conjuntoConfig->dian_numbering_ranges)
                    ? $conjuntoConfig->dian_numbering_ranges
                    : DianDataService::getDefaultNumberingRanges(),
                'municipalities' => is_array($conjuntoConfig->dian_municipalities)
                    ? $conjuntoConfig->dian_municipalities
                    : DianDataService::getDefaultMunicipalities(),
                'taxes' => is_array($conjuntoConfig->dian_taxes)
                    ? $conjuntoConfig->dian_taxes
                    : DianDataService::getDefaultTaxes(),
                'measurementUnits' => is_array($conjuntoConfig->dian_measurement_units)
                    ? $conjuntoConfig->dian_measurement_units
                    : DianDataService::getDefaultMeasurementUnits(),
                'companyInfo' => is_array($conjuntoConfig->dian_company_info)
                    ? $conjuntoConfig->dian_company_info
                    : (object) [],
                'technicalConfig' => is_array($conjuntoConfig->dian_technical_config)
                    ? $conjuntoConfig->dian_technical_config
                    : (object) [],
            ],
            'availableOptions' => [
                'municipalities' => DianDataService::getDefaultMunicipalities(),
                'taxes' => DianDataService::getDefaultTaxes(),
                'measurementUnits' => DianDataService::getDefaultMeasurementUnits(),
            ],
        ]);
    }

    public function update(Request $request)
    {
        $conjuntoConfig = ConjuntoConfig::where('is_active', true)->first();

        if (! $conjuntoConfig) {
            return redirect()->back()
                ->with('error', 'No hay configuración de conjunto activa');
        }

        $validated = $request->validate([
            'dian_electronic_invoicing_enabled' => 'boolean',
            'dian_electronic_invoicing_mode' => 'required|in:disabled,all,optional,required_amount',
            'dian_electronic_invoicing_min_amount' => 'nullable|numeric|min:0',
            'dian_numbering_ranges' => 'nullable|array',
            'dian_municipalities' => 'nullable|array',
            'dian_taxes' => 'nullable|array',
            'dian_measurement_units' => 'nullable|array',
            'dian_company_info' => 'nullable|array',
            'dian_company_info.nit' => 'required_if:dian_electronic_invoicing_enabled,true|string|max:20',
            'dian_company_info.business_name' => 'required_if:dian_electronic_invoicing_enabled,true|string|max:255',
            'dian_company_info.address' => 'required_if:dian_electronic_invoicing_enabled,true|string|max:255',
            'dian_technical_config' => 'nullable|array',
            'dian_technical_config.factus_base_url' => 'required_if:dian_electronic_invoicing_enabled,true|url',
            'dian_technical_config.factus_email' => 'required_if:dian_electronic_invoicing_enabled,true|email',
            'dian_technical_config.factus_password' => 'required_if:dian_electronic_invoicing_enabled,true|string',
            'dian_technical_config.factus_client_id' => 'required_if:dian_electronic_invoicing_enabled,true|string',
            'dian_technical_config.factus_client_secret' => 'required_if:dian_electronic_invoicing_enabled,true|string',
        ]);

        $conjuntoConfig->update($validated);

        return redirect()->back()
            ->with('success', 'Configuración DIAN actualizada exitosamente');
    }

    public function testFactusConnection(Request $request)
    {
        $validated = $request->validate([
            'base_url' => 'required|url',
            'email' => 'required|email',
            'password' => 'required|string',
            'client_id' => 'required|string',
            'client_secret' => 'required|string',
        ]);

        $factusService = new FactusApiService($validated);
        $result = $factusService->testConnection();

        return response()->json($result);
    }

    public function testFactusInvoice(Request $request)
    {
        $conjuntoConfig = ConjuntoConfig::where('is_active', true)->first();

        if (! $conjuntoConfig || ! $conjuntoConfig->dian_electronic_invoicing_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Facturación electrónica no está habilitada',
            ]);
        }

        $validated = $request->validate([
            'test_customer_email' => 'required|email',
            'test_customer_name' => 'required|string',
            'test_customer_document' => 'required|string',
        ]);

        // Create test invoice data
        $testInvoiceData = [
            'numbering_range_id' => 1, // Default test range
            'reference_code' => 'TEST-'.now()->format('YmdHis'),
            'document' => '01',
            'observation' => 'Factura de prueba - Integración DIAN',
            'issued_at' => now()->format('Y-m-d H:i:s'),
            'due_at' => now()->addDays(30)->format('Y-m-d H:i:s'),
            'customer' => [
                'identification' => [
                    'type' => '13',
                    'number' => $validated['test_customer_document'],
                ],
                'organization' => [
                    'name' => $validated['test_customer_name'],
                    'email' => $validated['test_customer_email'],
                    'phone' => '3001234567',
                    'address' => [
                        'line' => 'Calle 123 # 45-67',
                        'city' => '11001',
                        'department' => '11',
                        'postal_code' => '110111',
                    ],
                ],
                'legal_organization_type' => '13',
                'tributary' => [
                    'regime' => 'NO_APLICA',
                    'liability' => 'NO_APLICA',
                ],
            ],
            'items' => [
                [
                    'description' => 'Cuota de administración - Prueba',
                    'quantity' => 1.0,
                    'measurement_unit' => 'NIU',
                    'unit_price' => 100000.0,
                    'total_price' => 100000.0,
                    'discount_rate' => 0.0,
                    'discount_amount' => 0.0,
                    'tax_rate' => 0.0,
                    'tax_amount' => 0.0,
                    'tax_type' => 'IVA',
                ],
            ],
            'payment_method' => '10',
            'payment_due_date' => now()->addDays(30)->format('Y-m-d'),
            'currency' => 'COP',
            'subtotal' => 100000.0,
            'total_discount' => 0.0,
            'total_tax' => 0.0,
            'total' => 100000.0,
        ];

        $technicalConfig = $conjuntoConfig->dian_technical_config ?? [];
        $factusService = new FactusApiService([
            'base_url' => $technicalConfig['factus_base_url'] ?? '',
            'email' => $technicalConfig['factus_email'] ?? '',
            'password' => $technicalConfig['factus_password'] ?? '',
            'client_id' => $technicalConfig['factus_client_id'] ?? '',
            'client_secret' => $technicalConfig['factus_client_secret'] ?? '',
        ]);

        $result = $factusService->sendInvoice($testInvoiceData);

        return response()->json($result);
    }
}
