<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\PaymentAgreement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentAgreementController extends Controller
{
    public function index(Request $request)
    {
        $conjuntoConfig = ConjuntoConfig::first();

        $query = PaymentAgreement::with(['apartment.residents'])
            ->where('conjunto_config_id', $conjuntoConfig->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('apartment')) {
            $query->whereHas('apartment', function ($q) use ($request) {
                $q->where('number', 'like', "%{$request->apartment}%")
                    ->orWhere('tower', 'like', "%{$request->apartment}%");
            });
        }

        $agreements = $query->orderBy('created_at', 'desc')->paginate(15);

        $statusCounts = [
            'all' => PaymentAgreement::where('conjunto_config_id', $conjuntoConfig->id)->count(),
            'draft' => PaymentAgreement::where('conjunto_config_id', $conjuntoConfig->id)->byStatus('draft')->count(),
            'pending_approval' => PaymentAgreement::where('conjunto_config_id', $conjuntoConfig->id)->byStatus('pending_approval')->count(),
            'active' => PaymentAgreement::where('conjunto_config_id', $conjuntoConfig->id)->active()->count(),
            'breached' => PaymentAgreement::where('conjunto_config_id', $conjuntoConfig->id)->breached()->count(),
            'completed' => PaymentAgreement::where('conjunto_config_id', $conjuntoConfig->id)->byStatus('completed')->count(),
        ];

        return Inertia::render('PaymentAgreements/Index', [
            'agreements' => $agreements,
            'statusCounts' => $statusCounts,
            'filters' => $request->only(['status', 'apartment']),
        ]);
    }

    public function create(Request $request)
    {
        $conjuntoConfig = ConjuntoConfig::first();

        $apartment = null;
        $apartmentDebt = 0;
        $overdueInvoices = collect();

        if ($request->filled('apartment_id')) {
            $apartment = Apartment::with(['residents', 'invoices'])
                ->where('conjunto_config_id', $conjuntoConfig->id)
                ->findOrFail($request->apartment_id);

            $overdueInvoices = $apartment->invoices()
                ->overdue()
                ->orderBy('due_date')
                ->get();

            $apartmentDebt = $overdueInvoices->sum('balance_due');

            // If no overdue invoices found, use outstanding_balance
            if ($apartmentDebt == 0 && $apartment->outstanding_balance > 0) {
                $apartmentDebt = $apartment->outstanding_balance;
            }

            // If still 0, calculate based on monthly fee and overdue months
            if ($apartmentDebt == 0) {
                $monthsOverdue = match ($apartment->payment_status) {
                    'overdue_30' => 1,
                    'overdue_60' => 2,
                    'overdue_90' => 3,
                    'overdue_90_plus' => 4,
                    default => 0,
                };
                $apartmentDebt = $apartment->monthly_fee * $monthsOverdue;
            }
        }

        $delinquentApartments = Apartment::with(['residents', 'invoices'])
            ->where('conjunto_config_id', $conjuntoConfig->id)
            ->whereIn('payment_status', ['overdue_30', 'overdue_60', 'overdue_90', 'overdue_90_plus'])
            ->orderBy('tower')
            ->orderBy('number')
            ->get()
            ->map(function ($apt) {
                $debt = $apt->invoices()->where('status', 'overdue')
                    ->orWhere(function ($q) {
                        $q->where('status', 'pending')
                            ->where('due_date', '<', now());
                    })
                    ->sum('balance_due');

                // If no overdue invoices found, use outstanding_balance
                if ($debt == 0 && $apt->outstanding_balance > 0) {
                    $debt = $apt->outstanding_balance;
                }

                // If still 0, calculate based on monthly fee and overdue months
                if ($debt == 0) {
                    $monthsOverdue = match ($apt->payment_status) {
                        'overdue_30' => 1,
                        'overdue_60' => 2,
                        'overdue_90' => 3,
                        'overdue_90_plus' => 4,
                        default => 0,
                    };
                    $debt = $apt->monthly_fee * $monthsOverdue;
                }

                return [
                    'id' => $apt->id,
                    'full_address' => $apt->full_address,
                    'debt_amount' => $debt,
                    'residents' => $apt->residents->pluck('name')->join(', '),
                ];
            });

        return Inertia::render('PaymentAgreements/Create', [
            'apartment' => $apartment,
            'apartmentDebt' => $apartmentDebt,
            'overdueInvoices' => $overdueInvoices,
            'delinquentApartments' => $delinquentApartments,
        ]);
    }

    public function store(Request $request)
    {
        $conjuntoConfig = ConjuntoConfig::first();

        $validated = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'total_debt_amount' => 'required|numeric|min:0',
            'initial_payment' => 'nullable|numeric|min:0',
            'monthly_payment' => 'required|numeric|min:0',
            'installments' => 'required|integer|min:1|max:60',
            'start_date' => 'required|date|after_or_equal:today',
            'penalty_rate' => 'nullable|numeric|min:0|max:100',
            'terms_and_conditions' => 'required|string|min:10',
            'notes' => 'nullable|string',
        ]);

        $validated['conjunto_config_id'] = $conjuntoConfig->id;
        $validated['created_by'] = auth()->user()->name;
        $validated['end_date'] = now()->createFromFormat('Y-m-d', $validated['start_date'])
            ->addMonths($validated['installments'] - 1);

        $agreement = PaymentAgreement::create($validated);

        return redirect()->route('payment-agreements.show', $agreement)
            ->with('success', 'Acuerdo de pago creado exitosamente.');
    }

    public function show(PaymentAgreement $paymentAgreement)
    {
        $paymentAgreement->load([
            'apartment.residents',
            'installmentItems' => function ($query) {
                $query->orderBy('installment_number');
            },
        ]);

        return Inertia::render('PaymentAgreements/Show', [
            'agreement' => $paymentAgreement,
        ]);
    }

    public function edit(PaymentAgreement $paymentAgreement)
    {
        if (! in_array($paymentAgreement->status, ['draft', 'pending_approval'])) {
            return redirect()->route('payment-agreements.show', $paymentAgreement)
                ->with('error', 'Solo se pueden editar acuerdos en borrador o pendientes de aprobación.');
        }

        $paymentAgreement->load('apartment.residents');

        return Inertia::render('PaymentAgreements/Edit', [
            'agreement' => $paymentAgreement,
        ]);
    }

    public function update(Request $request, PaymentAgreement $paymentAgreement)
    {
        if (! in_array($paymentAgreement->status, ['draft', 'pending_approval'])) {
            return redirect()->route('payment-agreements.show', $paymentAgreement)
                ->with('error', 'Solo se pueden editar acuerdos en borrador o pendientes de aprobación.');
        }

        $validated = $request->validate([
            'total_debt_amount' => 'required|numeric|min:0',
            'initial_payment' => 'nullable|numeric|min:0',
            'monthly_payment' => 'required|numeric|min:0',
            'installments' => 'required|integer|min:1|max:60',
            'start_date' => 'required|date|after_or_equal:today',
            'penalty_rate' => 'nullable|numeric|min:0|max:100',
            'terms_and_conditions' => 'required|string|min:10',
            'notes' => 'nullable|string',
        ]);

        $validated['end_date'] = now()->createFromFormat('Y-m-d', $validated['start_date'])
            ->addMonths($validated['installments'] - 1);

        $paymentAgreement->update($validated);

        return redirect()->route('payment-agreements.show', $paymentAgreement)
            ->with('success', 'Acuerdo de pago actualizado exitosamente.');
    }

    public function destroy(PaymentAgreement $paymentAgreement)
    {
        if (! in_array($paymentAgreement->status, ['draft', 'pending_approval'])) {
            return redirect()->route('payment-agreements.index')
                ->with('error', 'Solo se pueden eliminar acuerdos en borrador o pendientes de aprobación.');
        }

        $paymentAgreement->delete();

        return redirect()->route('payment-agreements.index')
            ->with('success', 'Acuerdo de pago eliminado exitosamente.');
    }

    public function approve(Request $request, PaymentAgreement $paymentAgreement)
    {
        if ($paymentAgreement->status !== 'pending_approval') {
            return redirect()->back()
                ->with('error', 'Solo se pueden aprobar acuerdos pendientes de aprobación.');
        }

        $paymentAgreement->approve(auth()->user()->name);

        return redirect()->route('payment-agreements.show', $paymentAgreement)
            ->with('success', 'Acuerdo de pago aprobado exitosamente.');
    }

    public function activate(PaymentAgreement $paymentAgreement)
    {
        if ($paymentAgreement->status !== 'approved') {
            return redirect()->back()
                ->with('error', 'Solo se pueden activar acuerdos aprobados.');
        }

        $paymentAgreement->activate();

        return redirect()->route('payment-agreements.show', $paymentAgreement)
            ->with('success', 'Acuerdo de pago activado exitosamente.');
    }

    public function cancel(PaymentAgreement $paymentAgreement)
    {
        if (in_array($paymentAgreement->status, ['completed', 'cancelled'])) {
            return redirect()->back()
                ->with('error', 'No se puede cancelar un acuerdo completado o ya cancelado.');
        }

        $paymentAgreement->cancel();

        return redirect()->route('payment-agreements.show', $paymentAgreement)
            ->with('success', 'Acuerdo de pago cancelado exitosamente.');
    }

    public function submitForApproval(PaymentAgreement $paymentAgreement)
    {
        if ($paymentAgreement->status !== 'draft') {
            return redirect()->back()
                ->with('error', 'Solo se pueden enviar a aprobación acuerdos en borrador.');
        }

        $paymentAgreement->update(['status' => 'pending_approval']);

        return redirect()->route('payment-agreements.show', $paymentAgreement)
            ->with('success', 'Acuerdo enviado para aprobación exitosamente.');
    }

    public function recordPayment(Request $request, PaymentAgreement $paymentAgreement)
    {
        $validated = $request->validate([
            'installment_id' => 'required|exists:payment_agreement_installments,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string',
            'payment_reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $installment = $paymentAgreement->installmentItems()
            ->findOrFail($validated['installment_id']);

        if ($installment->status === 'paid') {
            return redirect()->back()
                ->with('error', 'Esta cuota ya está completamente pagada.');
        }

        $remainingAmount = $installment->amount - $installment->paid_amount;
        if ($validated['amount'] > $remainingAmount) {
            return redirect()->back()
                ->with('error', 'El monto excede el saldo pendiente de la cuota.');
        }

        $installment->markAsPaid(
            $validated['amount'],
            $validated['payment_method'],
            $validated['payment_reference']
        );

        if ($validated['notes']) {
            $installment->update(['notes' => $validated['notes']]);
        }

        $paymentAgreement->checkCompliance();

        return redirect()->route('payment-agreements.show', $paymentAgreement)
            ->with('success', 'Pago registrado exitosamente.');
    }
}
