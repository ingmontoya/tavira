<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Apartment;
use App\Models\PaymentConcept;
use App\Models\InvoiceItem;
use App\Models\ConjuntoConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();
        
        $query = Invoice::with(['apartment', 'items.paymentConcept'])
            ->where('conjunto_config_id', $conjunto->id);

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

        $invoices = $query->orderBy('created_at', 'desc')->paginate(15);

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

        $paymentConcepts = PaymentConcept::where('conjunto_config_id', $conjunto->id)
            ->where('is_active', true)
            ->get();

        return Inertia::render('Payments/Invoices/Create', [
            'apartments' => $apartments,
            'paymentConcepts' => $paymentConcepts,
            'apartmentId' => $request->apartment_id,
        ]);
    }

    public function store(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

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

        $invoice = Invoice::create([
            'conjunto_config_id' => $conjunto->id,
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
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['apartment.apartmentType', 'items.paymentConcept']);

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

        $paymentConcepts = PaymentConcept::where('conjunto_config_id', $conjunto->id)
            ->where('is_active', true)
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
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance_due,
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
        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $conjunto = ConjuntoConfig::where('is_active', true)->first();
        
        $existingInvoices = Invoice::where('conjunto_config_id', $conjunto->id)
            ->where('type', 'monthly')
            ->forPeriod($validated['year'], $validated['month'])
            ->count();

        if ($existingInvoices > 0) {
            return back()->withErrors(['error' => 'Ya existen facturas para este período.']);
        }

        $apartments = Apartment::where('conjunto_config_id', $conjunto->id)
            ->where('status', 'Occupied')
            ->get();

        $commonExpenseConcepts = PaymentConcept::where('conjunto_config_id', $conjunto->id)
            ->where('type', 'common_expense')
            ->where('is_active', true)
            ->where('is_recurring', true)
            ->get();

        $billingDate = now();
        $dueDate = $billingDate->copy()->addDays(15);
        $generatedCount = 0;

        foreach ($apartments as $apartment) {
            $invoice = Invoice::create([
                'conjunto_config_id' => $conjunto->id,
                'apartment_id' => $apartment->id,
                'type' => 'monthly',
                'billing_date' => $billingDate,
                'due_date' => $dueDate,
                'billing_period_year' => $validated['year'],
                'billing_period_month' => $validated['month'],
            ]);

            foreach ($commonExpenseConcepts as $concept) {
                if ($concept->isApplicableToApartmentType($apartment->apartment_type_id)) {
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
            }

            $invoice->calculateTotals();
            $generatedCount++;
        }

        return back()->with('success', "Se generaron {$generatedCount} facturas para el período seleccionado.");
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
        
        if (!$owner || !$owner->email) {
            return back()->withErrors(['email' => 'No se encontró un email válido para el propietario del apartamento.']);
        }

        try {
            // Generate PDF
            $pdf = PDF::loadView('invoices.pdf', compact('invoice'));
            
            // Send email with PDF attachment
            Mail::to($owner->email)->send(new InvoiceMail($invoice, $pdf->output()));
            
            return back()->with('success', 'Factura enviada por correo electrónico exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Error al enviar el correo electrónico: ' . $e->getMessage()]);
        }
    }
}
