<?php

namespace App\Http\Controllers;

use App\Exceptions\InvoiceGenerationException;
use App\Mail\InvoiceMail;
use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentConcept;
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
            ->orderBy('tower')->orderBy('number')->get();

        return Inertia::render('Payments/Invoices/Index', [
            'invoices' => $invoices,
            'apartments' => $apartments,
            'filters' => $request->only(['status', 'type', 'apartment_id', 'period', 'search']),
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

        return Inertia::render('Payments/Invoices/Create', [
            'apartments' => $apartments,
            'paymentConcepts' => $paymentConcepts,
            'apartmentId' => $request->apartment_id,
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
        $invoice->load(['apartment.apartmentType', 'items.paymentConcept']);

        $pdf = PDF::loadView('invoices.pdf', compact('invoice'));

        return $pdf->download("factura-{$invoice->invoice_number}.pdf");
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
}
