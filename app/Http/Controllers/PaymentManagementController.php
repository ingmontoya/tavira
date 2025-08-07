<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceiptMail;
use App\Models\Apartment;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class PaymentManagementController extends Controller
{
    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $query = Payment::forConjunto($conjunto->id)
            ->with(['apartment', 'createdBy', 'appliedBy', 'applications.invoice']);

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('apartment_id')) {
            $query->byApartment($request->apartment_id);
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->byPeriod($request->start_date, $request->end_date);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhereHas('apartment', function ($apartmentQuery) use ($search) {
                        $apartmentQuery->where('number', 'like', "%{$search}%");
                    });
            });
        }

        $payments = $query->orderBy('payment_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(50)
            ->withQueryString();

        $apartments = Apartment::whereIn('apartment_type_id', function ($query) use ($conjunto) {
            $query->select('id')
                ->from('apartment_types')
                ->where('conjunto_config_id', $conjunto->id);
        })->orderBy('number')->get();

        return Inertia::render('Finance/Payments/Index', [
            'payments' => $payments,
            'apartments' => $apartments,
            'filters' => $request->only(['status', 'apartment_id', 'start_date', 'end_date', 'search']),
            'statuses' => [
                'pending' => 'Pendiente',
                'aplicado' => 'Aplicado',
                'parcialmente_aplicado' => 'Parcialmente Aplicado',
                'reversado' => 'Reversado',
            ],
            'paymentMethods' => [
                'cash' => 'Efectivo',
                'bank_transfer' => 'Transferencia Bancaria',
                'check' => 'Cheque',
                'credit_card' => 'Tarjeta de Crédito',
                'debit_card' => 'Tarjeta Débito',
                'online' => 'Pago Online',
                'other' => 'Otro',
            ],
        ]);
    }

    public function create(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $apartments = Apartment::whereIn('apartment_type_id', function ($query) use ($conjunto) {
            $query->select('id')
                ->from('apartment_types')
                ->where('conjunto_config_id', $conjunto->id);
        })->orderBy('number')->get();

        $preSelectedApartment = null;
        $preSelectedInvoices = [];
        if ($request->filled('apartment_id')) {
            $preSelectedApartment = Apartment::find($request->apartment_id);

            if ($preSelectedApartment) {
                $preSelectedInvoices = Invoice::where('apartment_id', $request->apartment_id)
                    ->whereIn('status', ['pending', 'partial_payment', 'overdue'])
                    ->with(['items.paymentConcept'])
                    ->orderBy('billing_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->get()
                    ->map(function ($invoice) {
                        return [
                            'id' => $invoice->id,
                            'invoice_number' => $invoice->invoice_number,
                            'billing_date' => $invoice->billing_date,
                            'due_date' => $invoice->due_date,
                            'total_amount' => $invoice->total_amount,
                            'paid_amount' => $invoice->paid_amount,
                            'balance_amount' => $invoice->balance_amount,
                            'status' => $invoice->status,
                            'status_label' => $invoice->status_label,
                            'days_overdue' => $invoice->days_overdue,
                            'items' => $invoice->items->map(function ($item) {
                                return [
                                    'description' => $item->description,
                                    'amount' => $item->total_price,
                                    'concept' => $item->paymentConcept->name ?? 'Sin concepto',
                                ];
                            }),
                        ];
                    })->toArray();
            }
        }

        // Get accounting accounts for simulation
        $accountingAccounts = $this->getAccountingAccountsForSimulation($conjunto->id);

        return Inertia::render('Finance/Payments/Create', [
            'apartments' => $apartments,
            'preSelectedApartment' => $preSelectedApartment,
            'preSelectedInvoices' => $preSelectedInvoices,
            'paymentMethods' => [
                'cash' => 'Efectivo',
                'bank_transfer' => 'Transferencia Bancaria',
                'check' => 'Cheque',
                'credit_card' => 'Tarjeta de Crédito',
                'debit_card' => 'Tarjeta Débito',
                'online' => 'Pago Online',
                'other' => 'Otro',
            ],
            'accountingAccounts' => $accountingAccounts,
        ]);
    }

    public function store(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'total_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,check,credit_card,debit_card,online,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::transaction(function () use ($validated, $conjunto) {
            $payment = Payment::create([
                'conjunto_config_id' => $conjunto->id,
                'apartment_id' => $validated['apartment_id'],
                'total_amount' => $validated['total_amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'reference_number' => $validated['reference_number'],
                'notes' => $validated['notes'],
                'created_by' => auth()->id(),
            ]);

            // Automatically apply payment to invoices (FIFO)
            $applications = $payment->applyToInvoices();

            session()->flash('success', [
                'message' => 'Pago registrado y aplicado exitosamente.',
                'details' => [
                    'payment_number' => $payment->payment_number,
                    'total_amount' => $payment->total_amount,
                    'applied_amount' => $payment->applied_amount,
                    'remaining_amount' => $payment->remaining_amount,
                    'applications_count' => count($applications),
                ],
            ]);
        });

        return redirect()->route('finance.payments.index');
    }

    public function show(Payment $payment)
    {
        $payment->load([
            'apartment',
            'createdBy',
            'appliedBy',
            'applications.invoice.items.paymentConcept',
            'applications.createdBy',
        ]);

        // Load related accounting transactions
        $accountingTransactions = \App\Models\AccountingTransaction::where('reference_type', 'payment_application')
            ->whereIn('reference_id', $payment->applications->pluck('id'))
            ->with(['entries.account', 'entries.thirdParty', 'createdBy', 'reference.payment'])
            ->orderBy('transaction_date', 'desc')
            ->get();

        // Get accounting accounts for simulation
        $accountingAccounts = $this->getAccountingAccountsForSimulation($payment->conjunto_config_id);

        return Inertia::render('Finance/Payments/Show', [
            'payment' => $payment,
            'accountingTransactions' => $accountingTransactions,
            'accountingAccounts' => $accountingAccounts,
        ]);
    }

    public function edit(Payment $payment)
    {
        if (! $payment->is_pending) {
            return back()->withErrors(['payment' => 'Solo se pueden editar pagos pendientes.']);
        }

        $payment->load(['apartment']);

        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $apartments = Apartment::whereIn('apartment_type_id', function ($query) use ($conjunto) {
            $query->select('id')
                ->from('apartment_types')
                ->where('conjunto_config_id', $conjunto->id);
        })->orderBy('number')->get();

        return Inertia::render('Finance/Payments/Edit', [
            'payment' => $payment,
            'apartments' => $apartments,
            'paymentMethods' => [
                'cash' => 'Efectivo',
                'bank_transfer' => 'Transferencia Bancaria',
                'check' => 'Cheque',
                'credit_card' => 'Tarjeta de Crédito',
                'debit_card' => 'Tarjeta Débito',
                'online' => 'Pago Online',
                'other' => 'Otro',
            ],
        ]);
    }

    public function update(Request $request, Payment $payment)
    {
        if (! $payment->is_pending) {
            return back()->withErrors(['payment' => 'Solo se pueden editar pagos pendientes.']);
        }

        $validated = $request->validate([
            'apartment_id' => 'required|exists:apartments,id',
            'total_amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|in:cash,bank_transfer,check,credit_card,debit_card,online,other',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $payment->update($validated);

        return redirect()
            ->route('finance.payments.show', $payment)
            ->with('success', 'Pago actualizado exitosamente.');
    }

    public function destroy(Payment $payment)
    {
        if ($payment->applied_amount > 0) {
            return back()->withErrors(['payment' => 'No se pueden eliminar pagos que ya han sido aplicados a facturas.']);
        }

        $payment->delete();

        return redirect()
            ->route('finance.payments.index')
            ->with('success', 'Pago eliminado exitosamente.');
    }

    public function apply(Payment $payment)
    {
        if (! $payment->can_be_applied) {
            return back()->withErrors(['payment' => 'Este pago no puede ser aplicado.']);
        }

        try {
            DB::transaction(function () use ($payment) {
                $applications = $payment->applyToInvoices();

                session()->flash('success', [
                    'message' => 'Pago aplicado exitosamente.',
                    'details' => [
                        'applications_count' => count($applications),
                        'applied_amount' => $payment->applied_amount,
                        'remaining_amount' => $payment->remaining_amount,
                    ],
                ]);
            });

            return back();
        } catch (\Exception $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }

    public function reverse(Payment $payment)
    {
        if ($payment->applied_amount == 0) {
            return back()->withErrors(['payment' => 'Este pago no tiene aplicaciones para reversar.']);
        }

        try {
            DB::transaction(function () use ($payment) {
                $applications = $payment->applications()->active()->get();

                foreach ($applications as $application) {
                    $application->reverse();
                }

                session()->flash('success', [
                    'message' => 'Aplicaciones de pago reversadas exitosamente.',
                    'details' => [
                        'reversed_count' => $applications->count(),
                        'reversed_amount' => $applications->sum('amount_applied'),
                    ],
                ]);
            });

            return back();
        } catch (\Exception $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }

    public function getPendingInvoices(Request $request)
    {
        $apartmentId = $request->get('apartment_id');

        if (! $apartmentId) {
            return response()->json(['invoices' => []]);
        }

        $invoices = Invoice::where('apartment_id', $apartmentId)
            ->whereIn('status', ['pending', 'partial_payment', 'overdue'])
            ->with(['items.paymentConcept'])
            ->orderBy('billing_date', 'asc')
            ->orderBy('id', 'asc')
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'billing_date' => $invoice->billing_date,
                    'due_date' => $invoice->due_date,
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount,
                    'balance_amount' => $invoice->balance_amount,
                    'status' => $invoice->status,
                    'status_label' => $invoice->status_label,
                    'days_overdue' => $invoice->days_overdue,
                    'items' => $invoice->items->map(function ($item) {
                        return [
                            'description' => $item->description,
                            'amount' => $item->total_price,
                            'concept' => $item->paymentConcept->name ?? 'Sin concepto',
                        ];
                    }),
                ];
            });

        return response()->json(['invoices' => $invoices]);
    }

    public function getInvoicesForEdit(Request $request, Payment $payment)
    {
        $apartmentId = $request->get('apartment_id', $payment->apartment_id);

        if (! $apartmentId) {
            return response()->json(['invoices' => []]);
        }

        // Start with a simpler approach - get all invoices for the apartment
        $allInvoices = Invoice::where('apartment_id', $apartmentId)
            ->with(['items.paymentConcept', 'paymentApplications'])
            ->orderBy('billing_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $invoices = $allInvoices->map(function ($invoice) use ($payment) {
            // Calculate current applications from this payment
            $currentApplicationAmount = $invoice->paymentApplications
                ->where('payment_id', $payment->id)
                ->where('status', 'activo')
                ->sum('amount_applied');

            // Calculate what the balance would be WITHOUT this payment's applications
            $adjustedPaidAmount = $invoice->paid_amount - $currentApplicationAmount;
            $adjustedBalance = $invoice->total_amount - $adjustedPaidAmount;

            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'billing_date' => $invoice->billing_date,
                'due_date' => $invoice->due_date,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $adjustedPaidAmount,
                'balance_amount' => max(0, $adjustedBalance),
                'status' => $adjustedBalance > 0 ? ($invoice->due_date->isPast() ? 'overdue' : 'pending') : 'paid',
                'status_label' => $adjustedBalance > 0 ? ($invoice->due_date->isPast() ? 'Vencida' : 'Pendiente') : 'Pagada',
                'days_overdue' => $invoice->due_date->isPast() && $adjustedBalance > 0 ? $invoice->due_date->diffInDays(now()) : 0,
                'current_application_amount' => $currentApplicationAmount,
                'items' => $invoice->items->map(function ($item) {
                    return [
                        'description' => $item->description,
                        'amount' => $item->total_price,
                        'concept' => $item->paymentConcept->name ?? 'Sin concepto',
                    ];
                }),
            ];
        })
        // Only show invoices that would have a balance available for payment
            ->filter(function ($invoice) {
                return $invoice['balance_amount'] > 0;
            })
            ->values();

        return response()->json([
            'invoices' => $invoices,
            'debug' => [
                'apartment_id' => $apartmentId,
                'payment_id' => $payment->id,
                'total_invoices_found' => $allInvoices->count(),
                'filtered_invoices' => $invoices->count(),
                'all_invoice_ids' => $allInvoices->pluck('id'),
                'all_invoice_statuses' => $allInvoices->pluck('status'),
                'payment_applications_count' => $payment->applications()->count(),
                'raw_invoices' => $allInvoices->take(2)->map(function ($invoice) use ($payment) {
                    $appAmount = $invoice->paymentApplications
                        ->where('payment_id', $payment->id)
                        ->where('status', 'activo')
                        ->sum('amount_applied');

                    return [
                        'id' => $invoice->id,
                        'number' => $invoice->invoice_number,
                        'status' => $invoice->status,
                        'total_amount' => $invoice->total_amount,
                        'paid_amount' => $invoice->paid_amount,
                        'balance' => $invoice->total_amount - $invoice->paid_amount,
                        'current_app_amount' => $appAmount,
                        'adjusted_balance' => $invoice->total_amount - ($invoice->paid_amount - $appAmount),
                    ];
                }),
            ],
        ]);
    }

    public function sendByEmail(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'recipient_email' => 'nullable|email',
            'include_applications' => 'boolean',
            'custom_message' => 'nullable|string|max:1000',
        ]);

        $payment->load([
            'apartment',
            'createdBy',
            'appliedBy',
            'applications.invoice.items.paymentConcept',
            'applications.createdBy',
        ]);

        // Try to get recipient email from resident if not provided
        $recipientEmail = $validated['recipient_email'];
        if (! $recipientEmail) {
            $resident = Resident::where('apartment_id', $payment->apartment_id)
                ->where('is_owner', true)
                ->where('email', '!=', '')
                ->whereNotNull('email')
                ->first();

            if (! $resident) {
                return back()->withErrors(['email' => 'No se encontró un email de contacto para este apartamento. Por favor, especifique una dirección de correo.']);
            }

            $recipientEmail = $resident->email;
        }

        try {
            Mail::to($recipientEmail)->send(new PaymentReceiptMail([
                'payment' => $payment,
                'includeApplications' => $validated['include_applications'] ?? false,
                'customMessage' => $validated['custom_message'] ?? null,
            ]));

            return back()->with('success', [
                'message' => 'Comprobante de pago enviado exitosamente.',
                'details' => [
                    'recipient' => $recipientEmail,
                    'payment_number' => $payment->payment_number,
                ],
            ]);
        } catch (\Exception $e) {
            return back()->withErrors(['email' => 'Error al enviar el comprobante: '.$e->getMessage()]);
        }
    }

    private function getAccountingAccountsForSimulation(int $conjuntoConfigId): array
    {
        // Get the accounts needed for payment accounting simulation
        $accountCodes = [
            '110501', // Caja General
            '111001', // Banco Principal
            '130501', // CARTERA ADMINISTRACIÓN
        ];

        $accounts = ChartOfAccounts::forConjunto($conjuntoConfigId)
            ->whereIn('code', $accountCodes)
            ->select('id', 'code', 'name', 'account_type')
            ->get()
            ->keyBy('code');

        // Get payment method accounts from mapping
        $paymentMethods = \App\Models\PaymentMethodAccountMapping::getAvailablePaymentMethods();
        $paymentMethodAccounts = [];

        foreach ($paymentMethods as $method => $label) {
            $account = \App\Models\PaymentMethodAccountMapping::getCashAccountForPaymentMethod($conjuntoConfigId, $method);
            if ($account) {
                $paymentMethodAccounts[$method] = $account;
            }
        }

        return [
            'paymentMethodAccounts' => $paymentMethodAccounts,
            'adminReceivableAccount' => $accounts->get('130501'),
        ];
    }
}
