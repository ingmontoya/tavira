<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Resident;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountStatementController extends Controller
{
    public function index(Request $request): Response
    {
        // Get current user
        $user = $request->user();

        // Find resident by matching email with user email
        $resident = Resident::with(['apartment.apartmentType', 'apartment.conjuntoConfig'])
            ->where('email', $user->email)
            ->where('status', 'Active')
            ->first();

        if (! $resident || ! $resident->apartment) {
            // User is not linked to any apartment or resident record
            return Inertia::render('AccountStatement/Index', [
                'hasAccess' => false,
                'message' => 'No se encontró información de apartamento para su cuenta. Contacte al administrador.',
            ]);
        }

        $apartment = $resident->apartment;

        // Get date range (last 12 months by default, or from request)
        $startDate = $request->get('start_date', now()->subYear()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        if (is_string($startDate)) {
            $startDate = \Carbon\Carbon::parse($startDate);
        }
        if (is_string($endDate)) {
            $endDate = \Carbon\Carbon::parse($endDate);
        }

        // Get invoices for this apartment
        $invoices = Invoice::with(['items.paymentConcept', 'paymentApplications.payment'])
            ->where('apartment_id', $apartment->id)
            ->whereBetween('billing_date', [$startDate, $endDate])
            ->orderBy('billing_date', 'desc')
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'type' => $invoice->type,
                    'type_label' => $invoice->type_label,
                    'billing_date' => $invoice->billing_date,
                    'due_date' => $invoice->due_date,
                    'billing_period_label' => $invoice->billing_period_label,
                    'subtotal' => $invoice->subtotal,
                    'early_discount' => $invoice->early_discount,
                    'late_fees' => $invoice->late_fees,
                    'total_amount' => $invoice->total_amount,
                    'paid_amount' => $invoice->paid_amount,
                    'balance_amount' => $invoice->balance_amount,
                    'status' => $invoice->status,
                    'status_label' => $invoice->status_label,
                    'status_badge' => $invoice->status_badge,
                    'days_overdue' => $invoice->days_overdue,
                    'items' => $invoice->items->map(function ($item) {
                        return [
                            'concept_name' => $item->paymentConcept?->name ?? 'Concepto no especificado',
                            'description' => $item->description,
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->total_price,
                        ];
                    }),
                    'payments' => $invoice->paymentApplications->map(function ($application) {
                        return [
                            'payment_date' => $application->payment->payment_date,
                            'payment_number' => $application->payment->payment_number,
                            'payment_method' => $application->payment->payment_method_label,
                            'amount_applied' => $application->amount_applied,
                            'applied_date' => $application->applied_date,
                        ];
                    }),
                ];
            });

        // Get payments for this apartment (direct payments, not through invoices)
        $payments = Payment::with(['applications.invoice'])
            ->where('apartment_id', $apartment->id)
            ->whereBetween('payment_date', [$startDate, $endDate])
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_number' => $payment->payment_number,
                    'payment_date' => $payment->payment_date,
                    'total_amount' => $payment->total_amount,
                    'applied_amount' => $payment->applied_amount,
                    'remaining_amount' => $payment->remaining_amount,
                    'payment_method' => $payment->payment_method_label,
                    'reference_number' => $payment->reference_number,
                    'status' => $payment->status,
                    'status_label' => $payment->status_label,
                    'status_badge' => $payment->status_badge,
                    'notes' => $payment->notes,
                ];
            });

        // Calculate summary
        $totalInvoiced = $invoices->sum('total_amount');
        $totalPaid = $invoices->sum('paid_amount');
        $currentBalance = $invoices->sum('balance_amount');
        $overdueAmount = $invoices->where('status', 'vencido')->sum('balance_amount');

        // Get current month status
        $currentMonth = now();
        $currentMonthInvoice = Invoice::where('apartment_id', $apartment->id)
            ->where('billing_period_year', $currentMonth->year)
            ->where('billing_period_month', $currentMonth->month)
            ->first();

        return Inertia::render('AccountStatement/Index', [
            'hasAccess' => true,
            'resident' => [
                'full_name' => $resident->full_name,
                'document_type' => $resident->document_type,
                'document_number' => $resident->document_number,
                'email' => $resident->email,
                'phone' => $resident->phone,
                'resident_type' => $resident->resident_type,
                'apartment' => [
                    'full_address' => $apartment->full_address,
                    'tower' => $apartment->tower,
                    'number' => $apartment->number,
                    'type_name' => $apartment->apartmentType?->name,
                    'monthly_fee' => $apartment->monthly_fee,
                    'payment_status' => $apartment->payment_status,
                    'payment_status_badge' => $apartment->payment_status_badge,
                    'outstanding_balance' => $apartment->outstanding_balance,
                    'days_overdue' => $apartment->days_overdue,
                ],
            ],
            'summary' => [
                'total_invoiced' => $totalInvoiced,
                'total_paid' => $totalPaid,
                'current_balance' => $currentBalance,
                'overdue_amount' => $overdueAmount,
                'is_current' => $currentBalance <= 0,
                'current_month_invoice' => $currentMonthInvoice ? [
                    'id' => $currentMonthInvoice->id,
                    'invoice_number' => $currentMonthInvoice->invoice_number,
                    'due_date' => $currentMonthInvoice->due_date,
                    'total_amount' => $currentMonthInvoice->total_amount,
                    'balance_amount' => $currentMonthInvoice->balance_amount,
                    'status' => $currentMonthInvoice->status,
                    'status_label' => $currentMonthInvoice->status_label,
                    'status_badge' => $currentMonthInvoice->status_badge,
                ] : null,
            ],
            'invoices' => $invoices,
            'payments' => $payments,
            'date_range' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
            ],
            'available_months' => $this->getAvailableMonths($apartment->id),
        ]);
    }

    /**
     * Get available months with data for the apartment
     */
    private function getAvailableMonths(int $apartmentId): array
    {
        $months = Invoice::where('apartment_id', $apartmentId)
            ->selectRaw('DISTINCT billing_period_year, billing_period_month')
            ->orderBy('billing_period_year', 'desc')
            ->orderBy('billing_period_month', 'desc')
            ->get()
            ->map(function ($invoice) {
                $monthNames = [
                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                ];

                return [
                    'value' => $invoice->billing_period_year.'-'.str_pad($invoice->billing_period_month, 2, '0', STR_PAD_LEFT),
                    'label' => $monthNames[$invoice->billing_period_month].' '.$invoice->billing_period_year,
                    'year' => $invoice->billing_period_year,
                    'month' => $invoice->billing_period_month,
                ];
            });

        return $months->toArray();
    }

    /**
     * Show a specific invoice detail
     */
    public function showInvoice(Request $request, int $invoiceId): Response
    {
        $user = $request->user();

        // Find resident by email
        $resident = Resident::with(['apartment'])
            ->where('email', $user->email)
            ->where('status', 'Active')
            ->first();

        if (! $resident || ! $resident->apartment) {
            abort(403, 'No tienes acceso a esta información');
        }

        // Get invoice and verify it belongs to user's apartment
        $invoice = Invoice::with([
            'items.paymentConcept',
            'paymentApplications.payment',
            'apartment.apartmentType',
            'apartment.conjuntoConfig',
        ])
            ->where('id', $invoiceId)
            ->where('apartment_id', $resident->apartment->id)
            ->firstOrFail();

        return Inertia::render('AccountStatement/InvoiceDetail', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'type' => $invoice->type,
                'type_label' => $invoice->type_label,
                'billing_date' => $invoice->billing_date,
                'due_date' => $invoice->due_date,
                'billing_period_label' => $invoice->billing_period_label,
                'subtotal' => $invoice->subtotal,
                'early_discount' => $invoice->early_discount,
                'late_fees' => $invoice->late_fees,
                'total_amount' => $invoice->total_amount,
                'paid_amount' => $invoice->paid_amount,
                'balance_amount' => $invoice->balance_amount,
                'status' => $invoice->status,
                'status_label' => $invoice->status_label,
                'status_badge' => $invoice->status_badge,
                'days_overdue' => $invoice->days_overdue,
                'notes' => $invoice->notes,
                'items' => $invoice->items->map(function ($item) {
                    return [
                        'concept_name' => $item->paymentConcept?->name ?? 'Concepto no especificado',
                        'description' => $item->description,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                    ];
                }),
                'payments' => $invoice->paymentApplications->map(function ($application) {
                    return [
                        'payment_date' => $application->payment->payment_date,
                        'payment_number' => $application->payment->payment_number,
                        'payment_method' => $application->payment->payment_method_label,
                        'amount_applied' => $application->amount_applied,
                        'applied_date' => $application->applied_date,
                    ];
                }),
            ],
            'resident' => [
                'full_name' => $resident->full_name,
                'apartment' => [
                    'full_address' => $resident->apartment->full_address,
                    'tower' => $resident->apartment->tower,
                    'number' => $resident->apartment->number,
                ],
            ],
        ]);
    }
}
