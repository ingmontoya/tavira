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
            'total_apartments' => $this->getTotalApartmentsCount($conjuntoConfig->id),
        ];

        // Get detailed payment status breakdown like Dashboard
        $paymentsByStatus = $this->getPaymentsByStatus($conjuntoConfig->id);

        return Inertia::render('Payments/Index', [
            'stats' => $stats,
            'paymentsByStatus' => $paymentsByStatus,
        ]);
    }

    private function getPendingInvoicesCount(int $conjuntoConfigId): int
    {
        // Contar apartamentos con facturas pendientes dinámicamente
        return Apartment::where('conjunto_config_id', $conjuntoConfigId)
            ->whereHas('invoices', function ($query) {
                $query->whereIn('status', ['pending', 'overdue', 'partial']);
            })
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
        // Calcular apartamentos en mora dinámicamente basado en facturas reales
        $apartments = Apartment::where('conjunto_config_id', $conjuntoConfigId)
            ->with(['invoices' => function ($query) {
                $query->whereIn('status', ['pending', 'overdue', 'partial'])
                    ->orderBy('due_date', 'asc');
            }])
            ->get();

        $delinquentCount = 0;
        $today = now()->startOfDay();

        foreach ($apartments as $apartment) {
            $oldestUnpaidInvoice = $apartment->invoices->first();

            if ($oldestUnpaidInvoice) {
                $dueDate = $oldestUnpaidInvoice->due_date->startOfDay();

                if ($today->gt($dueDate)) {
                    $daysOverdue = $dueDate->diffInDays($today);
                    if ($daysOverdue >= 30) { // Consideramos mora después de 30 días
                        $delinquentCount++;
                    }
                }
            }
        }

        return $delinquentCount;
    }

    private function getActivePaymentConceptsCount(int $conjuntoConfigId): int
    {
        return PaymentConcept::where('conjunto_config_id', $conjuntoConfigId)
            ->active()
            ->count();
    }

    private function getTotalApartmentsCount(int $conjuntoConfigId): int
    {
        return Apartment::where('conjunto_config_id', $conjuntoConfigId)->count();
    }

    private function getPaymentsByStatus(int $conjuntoConfigId): \Illuminate\Support\Collection
    {
        // Same logic as Dashboard - calcular dinámicamente basado en facturas reales
        $apartments = Apartment::where('conjunto_config_id', $conjuntoConfigId)
            ->with(['invoices' => function ($query) {
                $query->whereIn('status', ['pending', 'overdue', 'partial'])
                    ->orderBy('due_date', 'asc');
            }])->get();

        $alDia = 0;
        $overdue30 = 0;
        $overdue60 = 0;
        $overdue90 = 0;
        $overdue90Plus = 0;

        foreach ($apartments as $apartment) {
            $oldestUnpaidInvoice = $apartment->invoices->first();

            if (! $oldestUnpaidInvoice) {
                // No hay facturas pendientes, apartamento al día
                $alDia++;
            } else {
                // Calcular días de mora
                $today = now()->startOfDay();
                $dueDate = $oldestUnpaidInvoice->due_date->startOfDay();

                if ($today->lte($dueDate)) {
                    // Aún no vencida
                    $alDia++;
                } else {
                    $daysOverdue = $dueDate->diffInDays($today);

                    if ($daysOverdue >= 90) {
                        $overdue90Plus++;
                    } elseif ($daysOverdue >= 60) {
                        $overdue90++;
                    } elseif ($daysOverdue >= 30) {
                        $overdue60++;
                    } else {
                        $overdue30++;
                    }
                }
            }
        }

        return collect([
            ['status' => 'Al día', 'count' => $alDia, 'color' => '#10b981'],
            ['status' => '30 días de mora', 'count' => $overdue30, 'color' => '#f59e0b'],
            ['status' => '60 días de mora', 'count' => $overdue60, 'color' => '#ef4444'],
            ['status' => '90 días de mora', 'count' => $overdue90, 'color' => '#dc2626'],
            ['status' => '+90 días de mora', 'count' => $overdue90Plus, 'color' => '#7f1d1d'],
        ])->filter(fn ($item) => $item['count'] > 0)->values();
    }
}
