<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\PaymentAgreementInstallment;
use App\Models\Resident;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $selectedMonth = $request->get('month', now()->format('Y-m'));
        [$selectedYear, $selectedMonthNum] = explode('-', $selectedMonth);
        $selectedYear = (int) $selectedYear;
        $selectedMonthNum = (int) $selectedMonthNum;
        // Obtener datos reales
        $totalResidents = Resident::count();
        $totalApartments = Apartment::count();
        $totalConjuntos = ConjuntoConfig::count();
        $conjunto = ConjuntoConfig::first(); // Obtener el conjunto único

        // Calcular crecimiento de residentes (mock data si no hay suficientes datos)
        $currentMonthResidents = Resident::whereMonth('created_at', now()->month)->count();
        $lastMonthResidents = Resident::whereMonth('created_at', now()->subMonth()->month)->count();
        $residentGrowth = $this->calculateGrowthPercentage($currentMonthResidents, $lastMonthResidents) ?: 15.2;

        // Obtener datos reales de pagos para el mes seleccionado
        $pendingPayments = $this->getPendingPaymentsCount($selectedYear, $selectedMonthNum);
        $expectedPayments = $this->getExpectedPaymentsCount($selectedYear, $selectedMonthNum);
        $totalPaymentsExpected = $this->getTotalExpectedAmount($selectedYear, $selectedMonthNum);
        $totalPaymentsReceived = $this->getTotalReceivedAmount($selectedYear, $selectedMonthNum);

        // KPIs - Combinando datos reales con mockeados
        $kpis = [
            'totalResidents' => max($totalResidents, 347), // Mínimo de demo
            'totalApartments' => max($totalApartments, 182), // Mínimo de demo
            'pendingPayments' => $pendingPayments,
            'expectedPayments' => $expectedPayments,
            'monthlyVisitors' => 1247, // Mock data - implementar después
            'residentGrowth' => $residentGrowth,
            'visitorGrowth' => 8.3, // Mock data
            'totalPaymentsExpected' => $totalPaymentsExpected,
            'totalPaymentsReceived' => $totalPaymentsReceived,
        ];

        // Residentes por Torre - Combinando datos reales con mock
        $residentsByTower = $this->getResidentsByTower();

        // Estados de pago - Datos reales para el mes seleccionado
        $paymentsByStatus = $this->getPaymentsByStatus($selectedYear, $selectedMonthNum);

        // Estado de ocupación - Combinando datos reales con mock
        $occupancyStatus = $this->getOccupancyStatus();

        // Gastos mensuales - Mock data
        $monthlyExpenses = collect([
            ['category' => 'Servicios Públicos', 'amount' => 2450000, 'percentage' => 35, 'color' => '#3b82f6'],
            ['category' => 'Mantenimiento', 'amount' => 1890000, 'percentage' => 27, 'color' => '#ef4444'],
            ['category' => 'Seguridad', 'amount' => 1200000, 'percentage' => 17, 'color' => '#10b981'],
            ['category' => 'Aseo', 'amount' => 780000, 'percentage' => 11, 'color' => '#f59e0b'],
            ['category' => 'Administración', 'amount' => 450000, 'percentage' => 6, 'color' => '#8b5cf6'],
            ['category' => 'Jardinería', 'amount' => 280000, 'percentage' => 4, 'color' => '#06b6d4'],
        ]);

        // Tendencia de recaudo - Mock data
        $paymentTrend = collect([
            ['month' => '2024-02', 'amount' => 15420000, 'label' => 'Feb 2024'],
            ['month' => '2024-03', 'amount' => 16890000, 'label' => 'Mar 2024'],
            ['month' => '2024-04', 'amount' => 15680000, 'label' => 'Abr 2024'],
            ['month' => '2024-05', 'amount' => 17230000, 'label' => 'May 2024'],
            ['month' => '2024-06', 'amount' => 16950000, 'label' => 'Jun 2024'],
            ['month' => '2024-07', 'amount' => 18120000, 'label' => 'Jul 2024'],
        ]);

        // Notificaciones pendientes - Mock data
        $pendingNotifications = collect([
            [
                'id' => 1,
                'title' => 'Corte de agua programado',
                'recipients_count' => 45,
                'type' => 'Comunicado',
                'created_at' => now()->subDays(2),
            ],
            [
                'id' => 2,
                'title' => 'Recordatorio pago administración',
                'recipients_count' => 23,
                'type' => 'Recordatorio',
                'created_at' => now()->subDays(1),
            ],
            [
                'id' => 3,
                'title' => 'Mantenimiento ascensores',
                'recipients_count' => 182,
                'type' => 'Aviso',
                'created_at' => now()->subHours(3),
            ],
        ]);

        return Inertia::render('Dashboard', [
            'kpis' => $kpis,
            'charts' => [
                'residentsByTower' => $residentsByTower,
                'paymentsByStatus' => $paymentsByStatus,
                'occupancyStatus' => $occupancyStatus,
                'monthlyExpenses' => $monthlyExpenses,
                'paymentTrend' => $paymentTrend,
            ],
            'pendingNotifications' => $pendingNotifications,
            'recentActivity' => $this->getRecentActivity(),
            'selectedMonth' => $selectedMonth,
            'availableMonths' => $this->getAvailableMonths(),
        ]);
    }

    private function calculateGrowthPercentage($current, $previous)
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function getResidentsByTower()
    {
        // Intentar obtener datos reales primero
        $realData = DB::table('apartments')
            ->join('residents', 'apartments.id', '=', 'residents.apartment_id')
            ->join('conjunto_configs', 'apartments.conjunto_config_id', '=', 'conjunto_configs.id')
            ->select('apartments.tower', 'conjunto_configs.name as conjunto_name', DB::raw('count(residents.id) as residents'))
            ->where('residents.status', 'Active')
            ->groupBy('apartments.tower', 'conjunto_configs.name')
            ->get();

        if ($realData->count() > 0) {
            return $realData->map(function ($item, $index) {
                return [
                    'name' => "Torre {$item->tower} - {$item->conjunto_name}",
                    'residents' => $item->residents,
                    'color' => $this->generateColor($index),
                ];
            })->values();
        }

        // Fallback a datos mock si no hay datos reales
        $conjunto = ConjuntoConfig::first(); // Obtener el conjunto único
        $conjuntoName = $conjunto ? $conjunto->name : 'Vista Hermosa';
        $towerNames = $conjunto && $conjunto->tower_names ? $conjunto->tower_names : ['A', 'B', 'C'];

        return collect([
            ['name' => "Torre {$towerNames[0]} - {$conjuntoName}", 'residents' => 89, 'color' => '#3b82f6'],
            ['name' => "Torre {$towerNames[1]} - {$conjuntoName}", 'residents' => 76, 'color' => '#ef4444'],
            ['name' => "Torre {$towerNames[2]} - {$conjuntoName}", 'residents' => 65, 'color' => '#10b981'],
        ]);
    }

    private function getOccupancyStatus()
    {
        // Intentar obtener datos reales primero
        $realData = DB::table('apartments')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

        if ($realData->count() > 0) {
            $statusColors = [
                'Occupied' => '#10b981',
                'Available' => '#3b82f6',
                'Maintenance' => '#f59e0b',
                'Reserved' => '#8b5cf6',
            ];

            $statusLabels = [
                'Occupied' => 'Ocupados',
                'Available' => 'Disponibles',
                'Maintenance' => 'Mantenimiento',
                'Reserved' => 'Reservados',
            ];

            return $realData->map(function ($item) use ($statusColors, $statusLabels) {
                return [
                    'status' => $statusLabels[$item->status] ?? $item->status,
                    'count' => $item->count,
                    'color' => $statusColors[$item->status] ?? '#6b7280',
                ];
            })->values();
        }

        // Fallback a datos mock
        return collect([
            ['status' => 'Ocupados', 'count' => 145, 'color' => '#10b981'],
            ['status' => 'Disponibles', 'count' => 28, 'color' => '#3b82f6'],
            ['status' => 'Mantenimiento', 'count' => 9, 'color' => '#f59e0b'],
        ]);
    }

    private function getRecentActivity()
    {
        // Intentar obtener actividad real primero
        $recentResidents = Resident::with('apartment')
            ->orderBy('created_at', 'desc')
            ->take(4)
            ->get()
            ->map(function ($resident) {
                return [
                    'type' => 'resident',
                    'message' => "Nuevo residente: {$resident->first_name} {$resident->last_name} en Apto ".(isset($resident->apartment->number) ? $resident->apartment->number : 'N/A'),
                    'time' => $resident->created_at->diffForHumans(),
                    'icon' => 'user-plus',
                ];
            });

        if ($recentResidents->count() > 0) {
            // Combinar con algunos datos mock adicionales
            $mockActivity = collect([
                [
                    'type' => 'payment',
                    'message' => 'Pago recibido: Apto 301 - Administración Julio',
                    'time' => 'hace 15 minutos',
                    'icon' => 'dollar-sign',
                ],
                [
                    'type' => 'visitor',
                    'message' => 'Visita registrada: María González en Torre 2',
                    'time' => 'hace 1 hora',
                    'icon' => 'user-check',
                ],
                [
                    'type' => 'maintenance',
                    'message' => 'Solicitud de mantenimiento: Ascensor Torre 1',
                    'time' => 'hace 2 horas',
                    'icon' => 'wrench',
                ],
                [
                    'type' => 'communication',
                    'message' => 'Comunicado enviado: Corte de agua programado',
                    'time' => 'hace 3 horas',
                    'icon' => 'bell',
                ],
            ]);

            return $recentResidents->concat($mockActivity)->take(8)->values();
        }

        // Fallback completo a datos mock
        return collect([
            [
                'type' => 'resident',
                'message' => 'Nuevo residente: Ana García en Apto 405',
                'time' => 'hace 5 minutos',
                'icon' => 'user-plus',
            ],
            [
                'type' => 'payment',
                'message' => 'Pago recibido: Apto 301 - Administración Julio',
                'time' => 'hace 15 minutos',
                'icon' => 'dollar-sign',
            ],
            [
                'type' => 'visitor',
                'message' => 'Visita registrada: Carlos Rodríguez en Torre 2',
                'time' => 'hace 45 minutos',
                'icon' => 'user-check',
            ],
            [
                'type' => 'maintenance',
                'message' => 'Solicitud de mantenimiento: Ascensor Torre 1',
                'time' => 'hace 1 hora',
                'icon' => 'wrench',
            ],
            [
                'type' => 'communication',
                'message' => 'Comunicado enviado: Asamblea general',
                'time' => 'hace 2 horas',
                'icon' => 'bell',
            ],
            [
                'type' => 'resident',
                'message' => 'Nuevo residente: Pedro Martínez en Apto 102',
                'time' => 'hace 3 horas',
                'icon' => 'user-plus',
            ],
            [
                'type' => 'payment',
                'message' => 'Pago recibido: Apto 205 - Administración Julio',
                'time' => 'hace 4 horas',
                'icon' => 'dollar-sign',
            ],
            [
                'type' => 'visitor',
                'message' => 'Visita registrada: Laura Fernández en Torre 3',
                'time' => 'hace 5 horas',
                'icon' => 'user-check',
            ],
        ]);
    }

    private function generateColor($id)
    {
        $colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'];

        return $colors[$id % count($colors)];
    }

    private function getPendingPaymentsCount(int $year, int $month): int
    {
        // Contar facturas pendientes para el período seleccionado
        $invoicesCount = Invoice::forPeriod($year, $month)
            ->whereIn('status', ['pending', 'overdue', 'partial'])
            ->count();

        // Contar cuotas de acuerdos de pago pendientes para el mes
        $installmentsCount = $this->getInstallmentsByMonth($year, $month)
            ->whereIn('status', ['pending', 'overdue'])
            ->count();

        return $invoicesCount + $installmentsCount;
    }

    private function getExpectedPaymentsCount(int $year, int $month): int
    {
        // Contar todas las facturas esperadas para el período (incluyendo pagadas)
        $invoicesCount = Invoice::forPeriod($year, $month)->count();

        // Contar todas las cuotas de acuerdos de pago esperadas para el mes
        $installmentsCount = $this->getInstallmentsByMonth($year, $month)->count();

        return $invoicesCount + $installmentsCount;
    }

    private function getTotalExpectedAmount(int $year, int $month): float
    {
        // Sumar montos esperados de facturas
        $invoicesAmount = Invoice::forPeriod($year, $month)->sum('total_amount');

        // Sumar montos esperados de cuotas de acuerdos de pago
        $installmentsAmount = $this->getInstallmentsByMonth($year, $month)->sum('amount');

        return $invoicesAmount + $installmentsAmount;
    }

    private function getTotalReceivedAmount(int $year, int $month): float
    {
        // Sumar montos recibidos de facturas
        $invoicesAmount = Invoice::forPeriod($year, $month)->sum('paid_amount');

        // Sumar montos recibidos de cuotas de acuerdos de pago
        $installmentsAmount = $this->getInstallmentsByMonth($year, $month)->sum('paid_amount');

        return $invoicesAmount + $installmentsAmount;
    }

    private function getPaymentsByStatus(int $year, int $month): \Illuminate\Support\Collection
    {
        // Obtener estadísticas de facturas para el período
        $invoiceStats = Invoice::forPeriod($year, $month)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Obtener estadísticas de cuotas de acuerdos para el mes
        $installmentStats = $this->getInstallmentsByMonth($year, $month)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        // Consolidar datos
        $paidCount = ($invoiceStats->get('paid')->count ?? 0) + ($installmentStats->get('paid')->count ?? 0);
        $pendingCount = ($invoiceStats->get('pending')->count ?? 0) + ($installmentStats->get('pending')->count ?? 0);
        $overdueCount = ($invoiceStats->get('overdue')->count ?? 0) + ($installmentStats->get('overdue')->count ?? 0);
        $partialCount = ($invoiceStats->get('partial')->count ?? 0) + ($installmentStats->get('partial')->count ?? 0);

        // Categorizar vencidos por días
        $overdue1to30 = 0;
        $overdue31to60 = 0;
        $overdueOver60 = 0;

        // Analizar facturas vencidas
        $overdueInvoices = Invoice::forPeriod($year, $month)
            ->where('status', 'overdue')
            ->get();

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = $invoice->days_overdue;
            if ($daysOverdue <= 30) {
                $overdue1to30++;
            } elseif ($daysOverdue <= 60) {
                $overdue31to60++;
            } else {
                $overdueOver60++;
            }
        }

        // Analizar cuotas vencidas
        $overdueInstallments = $this->getInstallmentsByMonth($year, $month)
            ->where('status', 'overdue')
            ->get();

        foreach ($overdueInstallments as $installment) {
            $daysOverdue = $installment->days_overdue;
            if ($daysOverdue <= 30) {
                $overdue1to30++;
            } elseif ($daysOverdue <= 60) {
                $overdue31to60++;
            } else {
                $overdueOver60++;
            }
        }

        return collect([
            ['status' => 'Al día', 'count' => $paidCount, 'color' => '#10b981'],
            ['status' => 'Pendiente', 'count' => $pendingCount, 'color' => '#3b82f6'],
            ['status' => 'Vencido 1-30 días', 'count' => $overdue1to30, 'color' => '#f59e0b'],
            ['status' => 'Vencido 31-60 días', 'count' => $overdue31to60, 'color' => '#ef4444'],
            ['status' => 'Vencido +60 días', 'count' => $overdueOver60, 'color' => '#7f1d1d'],
            ['status' => 'Pago parcial', 'count' => $partialCount, 'color' => '#8b5cf6'],
        ])->filter(fn ($item) => $item['count'] > 0)->values();
    }

    private function getAvailableMonths(): array
    {
        // Obtener rango de meses con datos de facturas o cuotas
        $invoiceMonths = Invoice::select(DB::raw('DISTINCT billing_period_year as year, billing_period_month as month'))
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get();

        $installmentMonths = $this->getDistinctMonthsFromInstallments();

        // Combinar y eliminar duplicados
        $allMonths = collect();

        foreach ($invoiceMonths as $month) {
            $allMonths->push(['year' => $month->year, 'month' => $month->month]);
        }

        foreach ($installmentMonths as $month) {
            $allMonths->push(['year' => (int) $month->year, 'month' => (int) $month->month]);
        }

        $uniqueMonths = $allMonths->unique(function ($item) {
            return $item['year'].'-'.$item['month'];
        })->sortByDesc(function ($item) {
            return $item['year'] * 12 + $item['month'];
        });

        // Si no hay datos, incluir el mes actual y algunos anteriores
        if ($uniqueMonths->isEmpty()) {
            $currentDate = now();
            $uniqueMonths = collect();
            for ($i = 0; $i < 6; $i++) {
                $date = $currentDate->copy()->subMonths($i);
                $uniqueMonths->push(['year' => $date->year, 'month' => $date->month]);
            }
        }

        // Formatear para el selector
        return $uniqueMonths->map(function ($item) {
            $monthNames = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
            ];

            return [
                'value' => sprintf('%04d-%02d', $item['year'], $item['month']),
                'label' => $monthNames[$item['month']].' '.$item['year'],
            ];
        })->values()->all();
    }

    /**
     * Helper method to get installments by month using database-agnostic date functions
     */
    private function getInstallmentsByMonth(int $year, int $month)
    {
        $driver = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            return PaymentAgreementInstallment::whereRaw('strftime("%Y", due_date) = ?', [$year])
                ->whereRaw('strftime("%m", due_date) = ?', [sprintf('%02d', $month)]);
        } else {
            // MySQL/PostgreSQL
            return PaymentAgreementInstallment::whereYear('due_date', $year)
                ->whereMonth('due_date', $month);
        }
    }

    /**
     * Helper method to get distinct months from installments using database-agnostic functions
     */
    private function getDistinctMonthsFromInstallments()
    {
        $driver = DB::connection()->getPdo()->getAttribute(\PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            return PaymentAgreementInstallment::select(
                DB::raw('DISTINCT strftime("%Y", due_date) as year, strftime("%m", due_date) as month')
            )
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();
        } else {
            // MySQL/PostgreSQL
            return PaymentAgreementInstallment::select(
                DB::raw('DISTINCT YEAR(due_date) as year, MONTH(due_date) as month')
            )
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();
        }
    }
}
