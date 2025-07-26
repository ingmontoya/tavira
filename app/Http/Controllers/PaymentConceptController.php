<?php

namespace App\Http\Controllers;

use App\Models\ApartmentType;
use App\Models\ConjuntoConfig;
use App\Models\PaymentConcept;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PaymentConceptController extends Controller
{
    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $query = PaymentConcept::where('conjunto_config_id', $conjunto->id);

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $concepts = $query->orderBy('type')->orderBy('name')->paginate(15);

        $filters = $request->only(['type', 'is_active', 'search']);
        
        // If no filters are applied, return null instead of empty array
        if (empty(array_filter($filters))) {
            $filters = null;
        }
        
        return Inertia::render('Payments/Concepts/Index', [
            'concepts' => $concepts,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $apartmentTypes = ApartmentType::where('conjunto_config_id', $conjunto->id)->get();

        return Inertia::render('Payments/Concepts/Create', [
            'apartmentTypes' => $apartmentTypes,
        ]);
    }

    public function store(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => ['required', Rule::in(['common_expense', 'sanction', 'parking', 'special', 'late_fee', 'other'])],
            'default_amount' => 'required|numeric|min:0',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
            'billing_cycle' => ['required', Rule::in(['monthly', 'quarterly', 'annually', 'one_time'])],
            'applicable_apartment_types' => 'nullable|array',
            'applicable_apartment_types.*' => 'exists:apartment_types,id',
        ]);

        PaymentConcept::create([
            'conjunto_config_id' => $conjunto->id,
            'name' => $validated['name'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'default_amount' => $validated['default_amount'],
            'is_recurring' => $validated['is_recurring'] ?? false,
            'is_active' => $validated['is_active'] ?? true,
            'billing_cycle' => $validated['billing_cycle'],
            'applicable_apartment_types' => $validated['applicable_apartment_types'],
        ]);

        return redirect()->route('payment-concepts.index')
            ->with('success', 'Concepto de pago creado exitosamente.');
    }

    public function show(PaymentConcept $paymentConcept)
    {
        $paymentConcept->load(['conjuntoConfig']);

        return Inertia::render('Payments/Concepts/Show', [
            'concept' => $paymentConcept,
        ]);
    }

    public function edit(PaymentConcept $paymentConcept)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $apartmentTypes = ApartmentType::where('conjunto_config_id', $conjunto->id)->get();

        return Inertia::render('Payments/Concepts/Edit', [
            'concept' => $paymentConcept,
            'apartmentTypes' => $apartmentTypes,
        ]);
    }

    public function update(Request $request, PaymentConcept $paymentConcept)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => ['required', Rule::in(['common_expense', 'sanction', 'parking', 'special', 'late_fee', 'other'])],
            'default_amount' => 'required|numeric|min:0',
            'is_recurring' => 'boolean',
            'is_active' => 'boolean',
            'billing_cycle' => ['required', Rule::in(['monthly', 'quarterly', 'annually', 'one_time'])],
            'applicable_apartment_types' => 'nullable|array',
            'applicable_apartment_types.*' => 'exists:apartment_types,id',
        ]);

        $paymentConcept->update([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'default_amount' => $validated['default_amount'],
            'is_recurring' => $request->boolean('is_recurring'),
            'is_active' => $request->boolean('is_active'),
            'billing_cycle' => $validated['billing_cycle'],
            'applicable_apartment_types' => $validated['applicable_apartment_types'],
        ]);

        return redirect()->route('payment-concepts.show', $paymentConcept)
            ->with('success', 'Concepto de pago actualizado exitosamente.');
    }

    public function destroy(PaymentConcept $paymentConcept)
    {
        if ($paymentConcept->invoiceItems()->exists()) {
            return back()->withErrors(['error' => 'No se puede eliminar un concepto que tiene facturas asociadas.']);
        }

        $paymentConcept->delete();

        return redirect()->route('payment-concepts.index')
            ->with('success', 'Concepto de pago eliminado exitosamente.');
    }

    public function toggle(PaymentConcept $paymentConcept)
    {
        $paymentConcept->update([
            'is_active' => ! $paymentConcept->is_active,
        ]);

        $status = $paymentConcept->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Concepto de pago {$status} exitosamente.");
    }
}
