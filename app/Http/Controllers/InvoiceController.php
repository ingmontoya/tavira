<?php

namespace App\Http\Controllers;

use App\Events\InvoiceCreated;
use App\Exceptions\InvoiceGenerationException;
use App\Http\Resources\InvoiceResource;
use App\Mail\InvoiceMail;
use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentConcept;
use App\Notifications\InvoiceGenerated;
use App\Services\NotificationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $query = Invoice::with(['apartment', 'items.paymentConcept']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('apartment_id')) {
            $query->where('apartment_id', $request->apartment_id);
        }

        if ($request->filled('period')) {
            [$year, $month] = explode('-', $request->period);
            $query->forPeriod($year, $month);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('apartment', function ($aq) use ($search) {
                        $aq->where('number', 'like', "%{$search}%");
                    });
            });
        }

        $invoices = $query->orderBy('created_at', 'desc')->paginate(25);

        $apartments = Apartment::where('conjunto_config_id', $conjunto->id)
            ->with('apartmentType')
            ->orderBy('tower')->orderBy('number')->get();

        // Check system readiness for invoicing
        $hasApartments = $apartments->isNotEmpty();
        $hasPaymentConcepts = PaymentConcept::where('is_active', true)->exists();
        $hasAccountingMappings = \App\Models\PaymentConceptAccountMapping::exists();
        $hasChartOfAccounts = \App\Models\ChartOfAccounts::exists();

        return Inertia::render('Payments/Invoices/Index', [
            'invoices' => $invoices,
            'apartments' => $apartments,
            'filters' => $request->only(['status', 'type', 'apartment_id', 'period', 'search']),
            'system_readiness' => [
                'has_apartments' => $hasApartments,
                'has_payment_concepts' => $hasPaymentConcepts,
                'has_accounting_mappings' => $hasAccountingMappings,
                'has_chart_of_accounts' => $hasChartOfAccounts,
                'is_ready' => $hasApartments && $hasPaymentConcepts && $hasAccountingMappings && $hasChartOfAccounts,
            ],
        ]);
    }

    public function create(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $apartments = Apartment::where('conjunto_config_id', $conjunto->id)
            ->with('apartmentType')
            ->orderBy('tower')->orderBy('number')->get();

        $paymentConcepts = PaymentConcept::where('is_active', true)
            ->get();

        // Check system readiness for invoicing
        $hasApartments = $apartments->isNotEmpty();
        $hasPaymentConcepts = $paymentConcepts->isNotEmpty();
        $hasAccountingMappings = \App\Models\PaymentConceptAccountMapping::exists();
        $hasChartOfAccounts = \App\Models\ChartOfAccounts::exists();

        return Inertia::render('Payments/Invoices/Create', [
            'apartments' => $apartments,
            'paymentConcepts' => $paymentConcepts,
            'apartmentId' => $request->apartment_id,
            'system_readiness' => [
                'has_apartments' => $hasApartments,
                'has_payment_concepts' => $hasPaymentConcepts,
                'has_accounting_mappings' => $hasAccountingMappings,
                'has_chart_of_accounts' => $hasChartOfAccounts,
                'is_ready' => $hasApartments && $hasPaymentConcepts && $hasAccountingMappings && $hasChartOfAccounts,
            ],
        ]);
    }

    public function store(Request $request)
    {
        try {
            $conjunto = ConjuntoConfig::where('is_active', true)->first();

            if (! $conjunto) {
                throw InvoiceGenerationException::noActiveConjunto();
            }

            $validated = $request->validate([
                'apartment_id' => 'required|exists:apartments,id',
                'type' => ['required', Rule::in(['monthly', 'individual', 'late_fee'])],
                'billing_date' => 'required|date',
                'due_date' => 'required|date|after:billing_date',
                'billing_period_year' => 'nullable|integer|min:2020|max:2030',
                'billing_period_month' => 'nullable|integer|min:1|max:12',
                'notes' => 'nullable|string|max:1000',
                'items' => 'required|array|min:1',
                'items.*.payment_concept_id' => 'required|exists:payment_concepts,id',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'items.*.notes' => 'nullable|string|max:500',
                'dian_observation' => 'nullable|string|max:1000',
                'dian_payment_method' => 'nullable|integer',
                'dian_currency' => 'nullable|string|size:3',
            ]);

            // Check for duplicate monthly invoice
            if ($validated['type'] === 'monthly' &&
                isset($validated['billing_period_year']) &&
                isset($validated['billing_period_month'])) {

                $existingInvoice = Invoice::where('apartment_id', $validated['apartment_id'])
                    ->where('type', 'monthly')
                    ->where('billing_period_year', $validated['billing_period_year'])
                    ->where('billing_period_month', $validated['billing_period_month'])
                    ->first();

                if ($existingInvoice) {
                    return back()->withErrors([
                        'billing_period' => 'Ya existe una factura mensual para este apartamento en el período seleccionado.',
                    ])->withInput();
                }
            }

            return DB::transaction(function () use ($validated) {
                $invoice = Invoice::create([
                    'apartment_id' => $validated['apartment_id'],
                    'type' => $validated['type'],
                    'billing_date' => $validated['billing_date'],
                    'due_date' => $validated['due_date'],
                    'billing_period_year' => $validated['billing_period_year'] ?? now()->year,
                    'billing_period_month' => $validated['billing_period_month'] ?? now()->month,
                    'notes' => $validated['notes'],
                    'dian_observation' => $validated['dian_observation'],
                    'dian_payment_method' => $validated['dian_payment_method'],
                    'dian_currency' => $validated['dian_currency'],
                ]);

                foreach ($validated['items'] as $itemData) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'payment_concept_id' => $itemData['payment_concept_id'],
                        'description' => $itemData['description'],
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $itemData['unit_price'],
                        'notes' => $itemData['notes'] ?? null,
                    ]);
                }

                $invoice->calculateTotals();
                $invoice->load('apartment');

                // Check if this invoice should be eligible for electronic invoicing
                $this->evaluateElectronicInvoicing($invoice);

                // Fire the InvoiceCreated event to generate accounting entries
                event(new InvoiceCreated($invoice));

                // Send notification to administrative users
                $notificationService = app(NotificationService::class);
                $notificationService->notifyAdministrative(new InvoiceGenerated($invoice));

                return redirect()->route('invoices.show', $invoice)
                    ->with('success', 'Factura creada exitosamente.');
            });
        } catch (InvoiceGenerationException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error inesperado al crear la factura: '.$e->getMessage()])->withInput();
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load([
            'apartment.apartmentType',
            'items.paymentConcept',
            'paymentApplications.payment.createdBy',
            'paymentApplications' => function ($query) {
                $query->orderBy('applied_date', 'desc');
            },
        ]);

        return Inertia::render('Payments/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $apartments = Apartment::where('conjunto_config_id', $conjunto->id)
            ->with('apartmentType')
            ->orderBy('tower')->orderBy('number')->get();

        $paymentConcepts = PaymentConcept::where('is_active', true)
            ->get();

        $invoice->load(['items.paymentConcept']);

        return Inertia::render('Payments/Invoices/Edit', [
            'invoice' => $invoice,
            'apartments' => $apartments,
            'paymentConcepts' => $paymentConcepts,
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return back()->withErrors(['error' => 'No se puede modificar una factura pagada o cancelada.']);
        }

        $validated = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'type' => ['required', Rule::in(['monthly', 'individual', 'late_fee'])],
            'billing_date' => 'required|date',
            'due_date' => 'required|date|after:billing_date',
            'billing_period_year' => 'nullable|integer|min:2020|max:2030',
            'billing_period_month' => 'nullable|integer|min:1|max:12',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.payment_concept_id' => 'required|exists:payment_concepts,id',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
        ]);

        $invoice->update([
            'apartment_id' => $validated['apartment_id'],
            'type' => $validated['type'],
            'billing_date' => $validated['billing_date'],
            'due_date' => $validated['due_date'],
            'billing_period_year' => $validated['billing_period_year'] ?? $invoice->billing_period_year,
            'billing_period_month' => $validated['billing_period_month'] ?? $invoice->billing_period_month,
            'notes' => $validated['notes'],
            'dian_observation' => $validated['dian_observation'],
            'dian_payment_method' => $validated['dian_payment_method'],
            'dian_currency' => $validated['dian_currency'],
        ]);

        $invoice->items()->delete();

        foreach ($validated['items'] as $itemData) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'payment_concept_id' => $itemData['payment_concept_id'],
                'description' => $itemData['description'],
                'quantity' => $itemData['quantity'],
                'unit_price' => $itemData['unit_price'],
                'notes' => $itemData['notes'] ?? null,
            ]);
        }

        $invoice->calculateTotals();

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Factura actualizada exitosamente.');
    }

    public function destroy(Invoice $invoice)
    {
        if (in_array($invoice->status, ['paid', 'partial'])) {
            return back()->withErrors(['error' => 'No se puede eliminar una factura con pagos registrados.']);
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Factura eliminada exitosamente.');
    }

    public function markAsPaid(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:'.$invoice->balance_due,
            'payment_method' => 'required|string|max:100',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        $invoice->markAsPaid(
            $validated['amount'],
            $validated['payment_method'],
            $validated['payment_reference']
        );

        return back()->with('success', 'Pago registrado exitosamente.');
    }

    public function generateMonthly(Request $request)
    {
        try {
            $validated = $request->validate([
                'year' => 'required|integer|min:2020|max:2030',
                'month' => 'required|integer|min:1|max:12',
            ]);

            $conjunto = ConjuntoConfig::where('is_active', true)->first();

            if (! $conjunto) {
                throw InvoiceGenerationException::noActiveConjunto();
            }

            // Check for existing invoices
            $existingInvoices = Invoice::where('type', 'monthly')
                ->forPeriod($validated['year'], $validated['month'])
                ->count();

            if ($existingInvoices > 0) {
                throw InvoiceGenerationException::duplicatePeriod($validated['year'], $validated['month'], $existingInvoices);
            }

            return DB::transaction(function () use ($conjunto, $validated) {
                // Sync payment concepts with current apartment types before generating invoices
                PaymentConcept::syncWithApartmentTypes($conjunto->id);

                $apartments = Apartment::where('conjunto_config_id', $conjunto->id)
                    ->whereIn('status', ['Occupied', 'Available'])
                    ->with('apartmentType')
                    ->get();

                if ($apartments->isEmpty()) {
                    throw InvoiceGenerationException::noOccupiedApartments();
                }

                $commonExpenseConcepts = PaymentConcept::where('type', 'common_expense')
                    ->where('is_active', true)
                    ->where('is_recurring', true)
                    ->where('billing_cycle', 'monthly')
                    ->get();

                if ($commonExpenseConcepts->isEmpty()) {
                    throw InvoiceGenerationException::noPaymentConcepts();
                }

                $billingDate = now();
                $dueDate = $billingDate->copy()->addDays(15);
                $generatedCount = 0;
                $skippedCount = 0;

                foreach ($apartments as $apartment) {
                    $applicableConcepts = $commonExpenseConcepts->filter(function ($concept) use ($apartment) {
                        return $concept->isApplicableToApartmentType($apartment->apartment_type_id);
                    });

                    if ($applicableConcepts->isEmpty()) {
                        $skippedCount++;

                        continue;
                    }

                    $invoice = Invoice::create([
                        'apartment_id' => $apartment->id,
                        'type' => 'monthly',
                        'billing_date' => $billingDate,
                        'due_date' => $dueDate,
                        'billing_period_year' => $validated['year'],
                        'billing_period_month' => $validated['month'],
                    ]);

                    foreach ($applicableConcepts as $concept) {
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'payment_concept_id' => $concept->id,
                            'description' => $concept->name,
                            'quantity' => 1,
                            'unit_price' => $concept->default_amount,
                            'period_start' => now()->createFromDate($validated['year'], $validated['month'], 1),
                            'period_end' => now()->createFromDate($validated['year'], $validated['month'], 1)->endOfMonth(),
                        ]);
                    }

                    $invoice->calculateTotals();

                    // Check if this invoice should be eligible for electronic invoicing
                    $this->evaluateElectronicInvoicing($invoice);

                    // Fire the InvoiceCreated event to generate accounting entries
                    event(new InvoiceCreated($invoice));

                    $generatedCount++;
                }

                $message = "Se generaron {$generatedCount} facturas para el período seleccionado.";
                if ($skippedCount > 0) {
                    $message .= " Se omitieron {$skippedCount} apartamentos sin conceptos aplicables.";
                }

                return back()->with('success', $message);
            });
        } catch (InvoiceGenerationException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error inesperado durante la generación: '.$e->getMessage()]);
        }
    }

    public function downloadPdf(Invoice $invoice)
    {
        $invoice->load([
            'apartment.apartmentType',
            'apartment.conjuntoConfig',
            'apartment.residents',
            'items.paymentConcept',
        ]);

        $pdf = PDF::loadView('invoices.recibo', compact('invoice'));

        return $pdf->download("recibo-{$invoice->invoice_number}.pdf");
    }

    public function sendByEmail(Invoice $invoice)
    {
        $invoice->load(['apartment.apartmentType', 'items.paymentConcept', 'apartment.residents']);

        // Get the apartment owner's email
        $owner = $invoice->apartment->residents->where('resident_type', 'Owner')->first();

        if (! $owner || ! $owner->email) {
            return back()->withErrors(['email' => 'No se encontró un email válido para el propietario del apartamento.']);
        }

        try {
            // Generate PDF
            $pdf = PDF::loadView('invoices.pdf', compact('invoice'));

            // Send email with PDF attachment
            Mail::to($owner->email)->send(new InvoiceMail($invoice, $pdf->output()));

            return back()->with('success', 'Factura enviada por correo electrónico exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Error al enviar el correo electrónico: '.$e->getMessage()]);
        }
    }

    /**
     * Send electronic invoice to DIAN via Factus
     */
    public function sendElectronicInvoice(Request $request, Invoice $invoice)
    {
        try {
            $electronicInvoicingService = app(\App\Services\ElectronicInvoicingService::class);

            // Check if electronic invoicing should be used for this invoice
            if (! $electronicInvoicingService->shouldUseElectronicInvoicing($invoice)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta factura no requiere facturación electrónica según la configuración actual.',
                ]);
            }

            // Send electronic invoice
            $result = $electronicInvoicingService->sendElectronicInvoice($invoice);

            if ($result['success']) {
                // Update invoice with electronic invoice data
                $invoice->update([
                    'electronic_invoice_status' => 'sent',
                    'electronic_invoice_uuid' => $result['uuid'] ?? null,
                    'electronic_invoice_cufe' => $result['cufe'] ?? null,
                    'electronic_invoice_sent_at' => now(),
                    'factus_id' => $result['factus_id'] ?? null,
                    'electronic_invoice_public_url' => $result['bill']['public_url'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Factura enviada exitosamente a la DIAN',
                    'uuid' => $result['uuid'] ?? null,
                    'cufe' => $result['cufe'] ?? null,
                ]);
            }

            // Update invoice with failed status
            $invoice->update([
                'electronic_invoice_status' => 'failed',
                'electronic_invoice_error' => $result['message'] ?? 'Error desconocido',
            ]);

            return response()->json($result);

        } catch (\Exception $e) {
            // Update invoice with failed status
            $invoice->update([
                'electronic_invoice_status' => 'failed',
                'electronic_invoice_error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al enviar factura electrónica: '.$e->getMessage(),
            ]);
        }
    }

    /**
     * Evaluate if this invoice should be eligible for electronic invoicing
     */
    private function evaluateElectronicInvoicing(Invoice $invoice): void
    {
        try {
            $electronicInvoicingService = app(\App\Services\ElectronicInvoicingService::class);

            // Check if electronic invoicing should be used for this invoice
            $shouldUse = $electronicInvoicingService->shouldUseElectronicInvoicing($invoice);

            $invoice->update([
                'can_be_electronic' => $shouldUse,
                'electronic_invoice_status' => $shouldUse ? 'pending' : null,
            ]);

        } catch (\Exception $e) {
            // If there's an error evaluating, default to false
            $invoice->update(['can_be_electronic' => false]);
        }
    }

    /**
     * Download electronic invoice PDF from Factus
     */
    public function downloadElectronicPdf(Invoice $invoice)
    {
        if (! $invoice->factus_id || $invoice->electronic_invoice_status !== 'sent') {
            return back()->withErrors(['error' => 'Esta factura no tiene una versión electrónica disponible para descargar.']);
        }

        try {
            $electronicInvoicingService = app(\App\Services\ElectronicInvoicingService::class);
            $conjunto = $invoice->apartment?->conjuntoConfig ?? ConjuntoConfig::where('is_active', true)->first();

            if (! $conjunto) {
                return back()->withErrors(['error' => 'No se encontró configuración del conjunto.']);
            }

            // Configure Factus service with conjunto's technical config
            $factusService = app(\App\Services\FactusApiService::class);
            $technicalConfig = $conjunto->dian_technical_config ?: [];

            $factusService->setConfig([
                'base_url' => $technicalConfig['factus_base_url'] ?? 'https://api-sandbox.factus.com.co',
                'email' => $technicalConfig['factus_email'] ?? '',
                'password' => $technicalConfig['factus_password'] ?? '',
                'client_id' => $technicalConfig['factus_client_id'] ?? '',
                'client_secret' => $technicalConfig['factus_client_secret'] ?? '',
            ]);

            $result = $factusService->getInvoicePdf($invoice->factus_id);

            if ($result['success']) {
                return response($result['data'])
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "attachment; filename=\"factura-electronica-{$invoice->invoice_number}.pdf\"");
            }

            return back()->withErrors(['error' => 'Error al descargar el PDF: '.$result['message']]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al descargar el PDF: '.$e->getMessage()]);
        }
    }

    /**
     * Download electronic invoice XML from Factus
     */
    public function downloadElectronicXml(Invoice $invoice)
    {
        if (! $invoice->factus_id || $invoice->electronic_invoice_status !== 'sent') {
            return back()->withErrors(['error' => 'Esta factura no tiene una versión electrónica disponible para descargar.']);
        }

        try {
            $electronicInvoicingService = app(\App\Services\ElectronicInvoicingService::class);
            $conjunto = $invoice->apartment?->conjuntoConfig ?? ConjuntoConfig::where('is_active', true)->first();

            if (! $conjunto) {
                return back()->withErrors(['error' => 'No se encontró configuración del conjunto.']);
            }

            // Configure Factus service with conjunto's technical config
            $factusService = app(\App\Services\FactusApiService::class);
            $technicalConfig = $conjunto->dian_technical_config ?: [];

            $factusService->setConfig([
                'base_url' => $technicalConfig['factus_base_url'] ?? 'https://api-sandbox.factus.com.co',
                'email' => $technicalConfig['factus_email'] ?? '',
                'password' => $technicalConfig['factus_password'] ?? '',
                'client_id' => $technicalConfig['factus_client_id'] ?? '',
                'client_secret' => $technicalConfig['factus_client_secret'] ?? '',
            ]);

            $result = $factusService->getInvoiceXml($invoice->factus_id);

            if ($result['success']) {
                return response($result['data'])
                    ->header('Content-Type', 'application/xml')
                    ->header('Content-Disposition', "attachment; filename=\"factura-electronica-{$invoice->invoice_number}.xml\"");
            }

            return back()->withErrors(['error' => 'Error al descargar el XML: '.$result['message']]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al descargar el XML: '.$e->getMessage()]);
        }
    }

    /**
     * View electronic invoice - prioritize public_url, fallback to PDF download
     */
    public function viewElectronicInvoice(Invoice $invoice)
    {
        try {
            // First, check if we have a stored public_url and redirect to it
            if ($invoice->electronic_invoice_public_url) {
                return redirect()->away($invoice->electronic_invoice_public_url);
            }

            $conjunto = $invoice->apartment?->conjuntoConfig ?? ConjuntoConfig::where('is_active', true)->first();

            if (! $conjunto) {
                return back()->withErrors(['error' => 'No se encontró configuración del conjunto.']);
            }

            // Configure Factus service with conjunto's technical config
            $factusService = app(\App\Services\FactusApiService::class);
            $technicalConfig = $conjunto->dian_technical_config ?: [];

            $factusService->setConfig([
                'base_url' => $technicalConfig['factus_base_url'] ?? 'https://api-sandbox.factus.com.co',
                'email' => $technicalConfig['factus_email'] ?? '',
                'password' => $technicalConfig['factus_password'] ?? '',
                'client_id' => $technicalConfig['factus_client_id'] ?? '',
                'client_secret' => $technicalConfig['factus_client_secret'] ?? '',
            ]);

            // Search for the invoice by reference code
            $searchResult = $factusService->searchInvoiceByReference($invoice->invoice_number);

            if (! $searchResult['success']) {
                return back()->withErrors(['error' => 'Error al buscar factura en Factus: '.$searchResult['message']]);
            }

            $responseData = $searchResult['data']['data'] ?? [];
            $bills = $responseData['data'] ?? [];

            if (empty($bills)) {
                return back()->withErrors(['error' => 'No se encontró la factura electrónica en Factus.']);
            }

            // Get the first bill (should only be one with unique reference code)
            $bill = $bills[0];
            $factusId = $bill['id'];
            $factusNumber = $bill['number']; // This is what we need for the PDF download
            $publicUrl = $bill['public_url'] ?? null;

            // Update the invoice with the factus_id and public_url if not set
            if (! $invoice->factus_id || ! $invoice->electronic_invoice_public_url) {
                $invoice->update([
                    'factus_id' => $factusId,
                    'electronic_invoice_uuid' => $bill['uuid'] ?? null,
                    'electronic_invoice_cufe' => $bill['cufe'] ?? null,
                    'electronic_invoice_status' => 'sent',
                    'electronic_invoice_public_url' => $publicUrl,
                ]);
            }

            // If we found a public_url, redirect to it
            if ($publicUrl) {
                return redirect()->away($publicUrl);
            }

            // Fallback: Get the PDF using the correct endpoint and invoice number
            $result = $factusService->getInvoicePdf($factusNumber);

            if ($result['success']) {
                return response($result['data'])
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "inline; filename=\"factura-electronica-{$invoice->invoice_number}.pdf\"");
            }

            return back()->withErrors(['error' => 'Error al obtener el PDF: '.$result['message']]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al ver la factura electrónica: '.$e->getMessage()]);
        }
    }

    // =====================================================
    // MOBILE API METHODS
    // =====================================================

    /**
     * API: Get account statement for authenticated user's apartment
     * Returns all invoices for the user's apartment with summary information
     */
    public function apiAccountStatement(Request $request)
    {
        try {
            $user = $request->user();

            // Get user's apartment through resident relationship
            $apartment = $user->apartment;

            if (! $apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró apartamento asociado al usuario.',
                ], 404);
            }

            // Get all invoices for the user's apartment
            $query = Invoice::with(['apartment', 'items.paymentConcept'])
                ->where('apartment_id', $apartment->id);

            // Optional filtering by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Optional filtering by year
            if ($request->filled('year')) {
                $query->where('billing_period_year', $request->year);
            }

            $invoices = $query->orderBy('billing_date', 'desc')->get();

            // Calculate summary statistics
            $totalPending = $invoices->where('status', 'pending')->sum('total_amount');
            $totalOverdue = $invoices->filter(function ($invoice) {
                return $invoice->status !== 'paid' && $invoice->due_date?->isPast();
            })->sum('total_amount');
            $totalPaid = $invoices->where('status', 'paid')->sum('total_amount');

            return response()->json([
                'success' => true,
                'data' => [
                    'apartment' => [
                        'number' => $apartment->number,
                        'tower' => $apartment->tower,
                        'type' => $apartment->apartmentType->name ?? null,
                    ],
                    'summary' => [
                        'total_pending' => $totalPending,
                        'total_overdue' => $totalOverdue,
                        'total_paid' => $totalPaid,
                        'invoice_count' => $invoices->count(),
                    ],
                    'invoices' => InvoiceResource::collection($invoices),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el estado de cuenta: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get paginated invoices for authenticated user's apartment
     * Returns invoices with pagination for mobile app listing
     */
    public function apiResidentIndex(Request $request)
    {
        try {
            $user = $request->user();

            // Get user's apartment through resident relationship
            $apartment = $user->apartment;

            if (! $apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró apartamento asociado al usuario.',
                ], 404);
            }

            // Build query for user's apartment invoices
            $query = Invoice::with(['apartment', 'items.paymentConcept'])
                ->where('apartment_id', $apartment->id);

            // Optional filtering by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Optional filtering by type
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            // Optional filtering by year
            if ($request->filled('year')) {
                $query->where('billing_period_year', $request->year);
            }

            // Optional filtering by month
            if ($request->filled('month')) {
                $query->where('billing_period_month', $request->month);
            }

            // Search by invoice number
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where('invoice_number', 'like', "%{$search}%");
            }

            // Order by most recent first
            $query->orderBy('billing_date', 'desc');

            // Paginate results (default 15 per page for mobile)
            $perPage = $request->get('per_page', 15);
            $invoices = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => InvoiceResource::collection($invoices),
                'meta' => [
                    'current_page' => $invoices->currentPage(),
                    'last_page' => $invoices->lastPage(),
                    'per_page' => $invoices->perPage(),
                    'total' => $invoices->total(),
                    'from' => $invoices->firstItem(),
                    'to' => $invoices->lastItem(),
                ],
                'apartment' => [
                    'number' => $apartment->number,
                    'tower' => $apartment->tower,
                    'type' => $apartment->apartmentType->name ?? null,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las facturas: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get detailed invoice information for authenticated user
     * Returns complete invoice details if it belongs to the user's apartment
     */
    public function apiShow(Request $request, Invoice $invoice)
    {
        try {
            $user = $request->user();

            // Get user's apartment through resident relationship
            $apartment = $user->apartment;

            if (! $apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró apartamento asociado al usuario.',
                ], 404);
            }

            // Verify the invoice belongs to the user's apartment
            if ($invoice->apartment_id !== $apartment->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para ver esta factura.',
                ], 403);
            }

            // Load relationships for complete invoice details
            $invoice->load([
                'apartment.apartmentType',
                'items.paymentConcept',
                'paymentApplications.payment.createdBy',
                'paymentApplications' => function ($query) {
                    $query->orderBy('applied_date', 'desc');
                },
            ]);

            return response()->json([
                'success' => true,
                'data' => new InvoiceResource($invoice),
                'payment_history' => $invoice->paymentApplications->map(function ($application) {
                    return [
                        'id' => $application->id,
                        'amount' => $application->applied_amount,
                        'applied_date' => $application->applied_date->toISOString(),
                        'payment_method' => $application->payment->payment_method ?? null,
                        'reference' => $application->payment->payment_reference ?? null,
                        'created_by' => $application->payment->createdBy->name ?? null,
                    ];
                }),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los detalles de la factura: '.$e->getMessage(),
            ], 500);
        }
    }
}
