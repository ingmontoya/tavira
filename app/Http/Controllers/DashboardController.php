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
        $user = $request->user();
        $selectedMonth = $request->get('month', now()->format('Y-m'));
        [$selectedYear, $selectedMonthNum] = explode('-', $selectedMonth);
        $selectedYear = (int) $selectedYear;
        $selectedMonthNum = (int) $selectedMonthNum;

        // Check user permissions to determine what data to show
        $canViewFullAdmin = $user->can('view_residents') && $user->can('view_apartments');
        $canViewPayments = $user->can('view_payments');
        $canViewReports = $user->can('view_reports');

        // Obtener datos reales (only if user has permissions)
        $totalResidents = $canViewFullAdmin ? Resident::count() : 0;
        $totalApartments = $canViewFullAdmin ? Apartment::count() : 0;
        $totalConjuntos = $canViewFullAdmin ? ConjuntoConfig::count() : 0;
        $conjunto = ConjuntoConfig::first(); // Obtener el conjunto único

        // Calcular crecimiento de residentes (mock data si no hay suficientes datos)
        $currentMonthResidents = $canViewFullAdmin ? Resident::whereMonth('created_at', now()->month)->count() : 0;
        $lastMonthResidents = $canViewFullAdmin ? Resident::whereMonth('created_at', now()->subMonth()->month)->count() : 0;
        $residentGrowth = $canViewFullAdmin ? ($this->calculateGrowthPercentage($currentMonthResidents, $lastMonthResidents) ?: 15.2) : 0;

        // Obtener datos reales de pagos para el mes seleccionado (only if user has permissions)
        $pendingPayments = $canViewPayments ? $this->getPendingPaymentsCount($selectedYear, $selectedMonthNum) : 0;
        $expectedPayments = $canViewPayments ? $this->getExpectedPaymentsCount($selectedYear, $selectedMonthNum) : 0;
        $totalPaymentsExpected = $canViewPayments ? $this->getTotalExpectedAmount($selectedYear, $selectedMonthNum) : 0;
        $totalPaymentsReceived = $canViewPayments ? $this->getTotalReceivedAmount($selectedYear, $selectedMonthNum) : 0;

        // KPIs - Usando datos reales del sistema
        $kpis = [
            'totalResidents' => $totalResidents,
            'totalApartments' => $totalApartments,
            'pendingPayments' => $pendingPayments,
            'expectedPayments' => $expectedPayments,
            'monthlyVisitors' => 1247, // Mock data - implementar después
            'residentGrowth' => $residentGrowth,
            'visitorGrowth' => 8.3, // Mock data
            'totalPaymentsExpected' => $totalPaymentsExpected,
            'totalPaymentsReceived' => $totalPaymentsReceived,
        ];

        // Residentes por Torre - Combinando datos reales con mock (only for admin users)
        $residentsByTower = $canViewFullAdmin ? $this->getResidentsByTower() : collect();

        // Estados de pago - Datos reales para el mes seleccionado (only for users with payment permissions)
        $paymentsByStatus = $canViewPayments ? $this->getPaymentsByStatus($selectedYear, $selectedMonthNum) : collect();

        // Estado de ocupación - Combinando datos reales con mock (only for admin users)
        $occupancyStatus = $canViewFullAdmin ? $this->getOccupancyStatus() : collect();

        // Gastos mensuales - Mock data (only for users with reports permission)
        $monthlyExpenses = $canViewReports ? collect([
            ['category' => 'Servicios Públicos', 'amount' => 2450000, 'percentage' => 35, 'color' => '#3b82f6'],
            ['category' => 'Mantenimiento', 'amount' => 1890000, 'percentage' => 27, 'color' => '#ef4444'],
            ['category' => 'Seguridad', 'amount' => 1200000, 'percentage' => 17, 'color' => '#10b981'],
            ['category' => 'Aseo', 'amount' => 780000, 'percentage' => 11, 'color' => '#f59e0b'],
            ['category' => 'Administración', 'amount' => 450000, 'percentage' => 6, 'color' => '#8b5cf6'],
            ['category' => 'Jardinería', 'amount' => 280000, 'percentage' => 4, 'color' => '#06b6d4'],
        ]) : collect();

        // Tendencia de recaudo - Mock data (only for users with payment permissions)
        $paymentTrend = $canViewPayments ? collect([
            ['month' => '2024-02', 'amount' => 15420000, 'label' => 'Feb 2024'],
            ['month' => '2024-03', 'amount' => 16890000, 'label' => 'Mar 2024'],
            ['month' => '2024-04', 'amount' => 15680000, 'label' => 'Abr 2024'],
            ['month' => '2024-05', 'amount' => 17230000, 'label' => 'May 2024'],
            ['month' => '2024-06', 'amount' => 16950000, 'label' => 'Jun 2024'],
            ['month' => '2024-07', 'amount' => 18120000, 'label' => 'Jul 2024'],
        ]) : collect();

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
            'recentActivity' => $canViewFullAdmin ? $this->getRecentActivity() : collect(),
            'selectedMonth' => $selectedMonth,
            'availableMonths' => $canViewPayments ? $this->getAvailableMonths() : [],
            'userPermissions' => [
                'canViewFullAdmin' => $canViewFullAdmin,
                'canViewPayments' => $canViewPayments,
                'canViewReports' => $canViewReports,
            ],
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
        // Contar apartamentos con facturas pendientes dinámicamente
        $conjuntoConfig = ConjuntoConfig::first();
        if (! $conjuntoConfig) {
            return 0;
        }

        return Apartment::where('conjunto_config_id', $conjuntoConfig->id)
            ->whereHas('invoices', function ($query) {
                $query->whereIn('status', ['pending', 'overdue', 'partial']);
            })->count();
    }

    private function getExpectedPaymentsCount(int $year, int $month): int
    {
        // Contar todos los apartamentos del conjunto (este es el "total esperado")
        $conjuntoConfig = ConjuntoConfig::first();
        if (! $conjuntoConfig) {
            return 0;
        }

        return Apartment::where('conjunto_config_id', $conjuntoConfig->id)->count();
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
        // Calcular dinámicamente basado en facturas reales, no en payment_status almacenado
        $conjuntoConfig = ConjuntoConfig::first();
        if (! $conjuntoConfig) {
            return collect();
        }

        $apartments = Apartment::where('conjunto_config_id', $conjuntoConfig->id)
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
        } elseif ($driver === 'pgsql') {
            // PostgreSQL
            return PaymentAgreementInstallment::whereRaw('EXTRACT(YEAR FROM due_date) = ?', [$year])
                ->whereRaw('EXTRACT(MONTH FROM due_date) = ?', [$month]);
        } else {
            // MySQL
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
        } elseif ($driver === 'pgsql') {
            // PostgreSQL
            return PaymentAgreementInstallment::select(
                DB::raw('DISTINCT EXTRACT(YEAR FROM due_date) as year, EXTRACT(MONTH FROM due_date) as month')
            )
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();
        } else {
            // MySQL
            return PaymentAgreementInstallment::select(
                DB::raw('DISTINCT YEAR(due_date) as year, MONTH(due_date) as month')
            )
                ->orderBy('year', 'desc')
                ->orderBy('month', 'desc')
                ->get();
        }
    }
}
