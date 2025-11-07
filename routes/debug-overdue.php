<?php

use App\Http\Controllers\DashboardController;
use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\Support\Facades\Route;

/**
 * RUTA TEMPORAL DE DEBUG PARA PAGOS VENCIDOS
 * Eliminar después de verificar que funciona correctamente
 */

Route::get('/debug/overdue-payments', function () {
    $conjunto = ConjuntoConfig::first();

    if (! $conjunto) {
        return response()->json(['error' => 'No hay conjunto configurado']);
    }

    // Get current selection (November 2025)
    $currentYear = 2025;
    $currentMonth = 11;

    $currentMonthStart = Carbon::create($currentYear, $currentMonth, 1)->startOfMonth();

    // Query 1: With period fields
    $invoicesWithPeriod = Invoice::where(function ($query) use ($currentYear, $currentMonth) {
        $query->where('billing_period_year', '<', $currentYear)
            ->orWhere(function ($q2) use ($currentYear, $currentMonth) {
                $q2->where('billing_period_year', '=', $currentYear)
                    ->where('billing_period_month', '<', $currentMonth);
            });
    })
        ->whereIn('status', ['pendiente', 'vencido', 'pago_parcial'])
        ->where('balance_amount', '>', 0)
        ->get();

    // Query 2: With billing_date
    $invoicesWithDate = Invoice::where('billing_date', '<', $currentMonthStart)
        ->whereIn('status', ['pendiente', 'vencido', 'pago_parcial'])
        ->where('balance_amount', '>', 0)
        ->get();

    // Query 3: All unpaid invoices (for comparison)
    $allUnpaidInvoices = Invoice::whereIn('status', ['pendiente', 'vencido', 'pago_parcial'])
        ->where('balance_amount', '>', 0)
        ->get();

    // Query 4: Combined query (what the controller uses)
    $overdueInvoices = Invoice::where(function ($query) use ($currentYear, $currentMonth, $currentMonthStart) {
        $query->where(function ($q) use ($currentYear, $currentMonth) {
            $q->where('billing_period_year', '<', $currentYear)
                ->orWhere(function ($q2) use ($currentYear, $currentMonth) {
                    $q2->where('billing_period_year', '=', $currentYear)
                        ->where('billing_period_month', '<', $currentMonth);
                });
        })
            ->orWhere(function ($q) use ($currentMonthStart) {
                $q->whereNull('billing_period_year')
                    ->where('billing_date', '<', $currentMonthStart);
            });
    })
        ->whereIn('status', ['pendiente', 'vencido', 'pago_parcial'])
        ->where('balance_amount', '>', 0)
        ->with('apartment')
        ->get();

    // Calculate metrics
    $totalOverdueAmount = 0;
    $totalDaysOverdue = 0;
    $overdueCount = 0;
    $overdueApartments = [];

    foreach ($overdueInvoices as $invoice) {
        $totalOverdueAmount += $invoice->balance_amount;
        $overdueApartments[$invoice->apartment_id] = true;

        $today = now()->startOfDay();
        $dueDate = $invoice->due_date->startOfDay();
        $daysOverdue = $today->greaterThan($dueDate) ? $dueDate->diffInDays($today) : 0;

        if ($daysOverdue > 0) {
            $totalDaysOverdue += $daysOverdue;
            $overdueCount++;
        }
    }

    return response()->json([
        'debug_info' => [
            'conjunto' => $conjunto->name,
            'current_year' => $currentYear,
            'current_month' => $currentMonth,
            'current_month_start' => $currentMonthStart->toDateString(),
        ],
        'query_results' => [
            'with_period_fields' => [
                'count' => $invoicesWithPeriod->count(),
                'invoices' => $invoicesWithPeriod->map(fn ($inv) => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'billing_period_year' => $inv->billing_period_year,
                    'billing_period_month' => $inv->billing_period_month,
                    'billing_date' => $inv->billing_date,
                    'status' => $inv->status,
                    'balance_amount' => $inv->balance_amount,
                ]),
            ],
            'with_billing_date' => [
                'count' => $invoicesWithDate->count(),
                'invoices' => $invoicesWithDate->map(fn ($inv) => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'billing_date' => $inv->billing_date,
                    'status' => $inv->status,
                    'balance_amount' => $inv->balance_amount,
                ]),
            ],
            'all_unpaid' => [
                'count' => $allUnpaidInvoices->count(),
            ],
            'combined_query' => [
                'count' => $overdueInvoices->count(),
                'invoices' => $overdueInvoices->map(fn ($inv) => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'apartment' => $inv->apartment->full_address ?? 'N/A',
                    'billing_period_year' => $inv->billing_period_year,
                    'billing_period_month' => $inv->billing_period_month,
                    'billing_date' => $inv->billing_date,
                    'due_date' => $inv->due_date,
                    'status' => $inv->status,
                    'balance_amount' => $inv->balance_amount,
                ]),
            ],
        ],
        'calculated_metrics' => [
            'total_overdue_apartments' => count($overdueApartments),
            'total_overdue_amount' => $totalOverdueAmount,
            'total_overdue_invoices' => $overdueInvoices->count(),
            'average_days_overdue' => $overdueCount > 0 ? round($totalDaysOverdue / $overdueCount) : 0,
        ],
    ]);
})->middleware(['web', 'auth'])->name('debug.overdue');
