<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\PaymentConcept;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    public function index(): Response
    {
        $conjuntoConfig = ConjuntoConfig::first();

        if (! $conjuntoConfig) {
            return Inertia::render('Payments/Index', [
                'stats' => [
                    'pending_invoices' => 0,
                    'monthly_collection' => 0,
                    'delinquent_apartments' => 0,
                    'active_concepts' => 0,
                ],
            ]);
        }

        $stats = [
            'pending_invoices' => $this->getPendingInvoicesCount($conjuntoConfig->id),
            'monthly_collection' => $this->getMonthlyCollection($conjuntoConfig->id),
            'delinquent_apartments' => $this->getDelinquentApartmentsCount($conjuntoConfig->id),
            'active_concepts' => $this->getActivePaymentConceptsCount($conjuntoConfig->id),
        ];

        return Inertia::render('Payments/Index', [
            'stats' => $stats,
        ]);
    }

    private function getPendingInvoicesCount(int $conjuntoConfigId): int
    {
        return Invoice::where('conjunto_config_id', $conjuntoConfigId)
            ->whereIn('status', ['pending', 'partial'])
            ->count();
    }

    private function getMonthlyCollection(int $conjuntoConfigId): float
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        return Invoice::where('conjunto_config_id', $conjuntoConfigId)
            ->where('status', 'paid')
            ->whereMonth('paid_date', $currentMonth)
            ->whereYear('paid_date', $currentYear)
            ->sum('paid_amount');
    }

    private function getDelinquentApartmentsCount(int $conjuntoConfigId): int
    {
        return Apartment::where('conjunto_config_id', $conjuntoConfigId)
            ->whereHas('invoices', function ($query) {
                $query->overdue();
            })
            ->distinct()
            ->count();
    }

    private function getActivePaymentConceptsCount(int $conjuntoConfigId): int
    {
        return PaymentConcept::where('conjunto_config_id', $conjuntoConfigId)
            ->active()
            ->count();
    }
}
