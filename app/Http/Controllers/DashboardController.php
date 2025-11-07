<?php

namespace App\Http\Controllers;

use App\Models\AccountingTransaction;
use App\Models\Apartment;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\PaymentAgreementInstallment;
use App\Models\PaymentConcept;
use App\Models\PaymentConceptAccountMapping;
use App\Models\Resident;
use App\Models\Visit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Check if user is a resident or propietario (should use resident dashboard)
        // But only redirect if they have an apartment assigned to avoid redirect loop
        if ($user->hasRole(['residente', 'propietario'])) {
            // Check if user has an apartment before redirecting
            if ($user->apartment) {
                return redirect()->route('resident.dashboard');
            }
            // If no apartment, let them stay on this dashboard but with limited access
        }

        $selectedMonth = $request->get('month', now()->format('Y-m'));
        [$selectedYear, $selectedMonthNum] = explode('-', $selectedMonth);
        $selectedYear = (int) $selectedYear;
        $selectedMonthNum = (int) $selectedMonthNum;

        // Check user permissions to determine what data to show (for admin users)
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

        // Calcular visitas del mes actual (datos reales)
        $currentMonthVisits = $canViewFullAdmin ? $this->getCurrentMonthVisitsCount() : 0;
        $lastMonthVisits = $canViewFullAdmin ? $this->getLastMonthVisitsCount() : 0;
        $visitorGrowth = $canViewFullAdmin ? $this->calculateGrowthPercentage($currentMonthVisits, $lastMonthVisits) : 0;

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
            'monthlyVisitors' => $currentMonthVisits, // Datos reales de visitas
            'residentGrowth' => $residentGrowth,
            'visitorGrowth' => $visitorGrowth, // Datos reales de crecimiento de visitas
            'totalPaymentsExpected' => $totalPaymentsExpected,
            'totalPaymentsReceived' => $totalPaymentsReceived,
        ];

        // Residentes por Torre - Combinando datos reales con mock (only for admin users)
        $residentsByTower = $canViewFullAdmin ? $this->getResidentsByTower() : collect();

        // Estados de pago - Datos reales para el mes seleccionado (only for users with payment permissions)
        $paymentsByStatus = $canViewPayments ? $this->getPaymentsByStatus($selectedYear, $selectedMonthNum) : collect();

        // Estado de ocupación - Combinando datos reales con mock (only for admin users)
        $occupancyStatus = $canViewFullAdmin ? $this->getOccupancyStatus() : collect();

        // Gastos mensuales - Datos reales de accounting transactions (only for users with reports permission)
        $monthlyExpenses = $canViewReports ? $this->getMonthlyExpenses($selectedYear, $selectedMonthNum) : collect();

        // Tendencia de recaudo - Datos reales de pagos (only for users with payment permissions)
        $paymentTrend = $canViewPayments ? $this->getPaymentTrend() : collect();

        // Notificaciones pendientes - Datos reales del sistema
        $pendingNotifications = $this->getPendingNotifications();

        // Check accounting system readiness (only for users who can manage accounting)
        $accountingReadiness = $user->can('manage_accounting')
            ? $this->getAccountingSystemReadiness()
            : ['needs_wizard' => false];

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
            'accountingReadiness' => $accountingReadiness,
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
        // Obtener datos reales únicamente
        $realData = DB::table('apartments')
            ->join('residents', 'apartments.id', '=', 'residents.apartment_id')
            ->join('conjunto_configs', 'apartments.conjunto_config_id', '=', 'conjunto_configs.id')
            ->select('apartments.tower', 'conjunto_configs.name as conjunto_name', DB::raw('count(residents.id) as residents'))
            ->where('residents.status', 'Active')
            ->groupBy('apartments.tower', 'conjunto_configs.name')
            ->get();

        return $realData->map(function ($item, $index) {
            return [
                'name' => "Torre {$item->tower} - {$item->conjunto_name}",
                'residents' => $item->residents,
                'color' => $this->generateColor($index),
            ];
        })->values();
    }

    private function getOccupancyStatus()
    {
        // Obtener datos reales únicamente
        $realData = DB::table('apartments')
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get();

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

    private function getRecentActivity()
    {
        $activities = collect();

        // Actividad de nuevos residentes
        $recentResidents = Resident::with('apartment')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($resident) {
                return [
                    'type' => 'resident',
                    'message' => "Nuevo residente: {$resident->first_name} {$resident->last_name} en Apto ".(isset($resident->apartment->number) ? $resident->apartment->number : 'N/A'),
                    'time' => $resident->created_at->diffForHumans(),
                    'icon' => 'user-plus',
                    'timestamp' => $resident->created_at,
                ];
            });

        // Actividad de pagos recientes
        $recentPayments = Invoice::where('paid_amount', '>', 0)
            ->with('apartment')
            ->orderBy('updated_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($invoice) {
                return [
                    'type' => 'payment',
                    'message' => "Pago recibido: Apto {$invoice->apartment->number} - ".number_format($invoice->paid_amount, 0, ',', '.'),
                    'time' => $invoice->updated_at->diffForHumans(),
                    'icon' => 'dollar-sign',
                    'timestamp' => $invoice->updated_at,
                ];
            });

        // Actividad de visitas recientes
        $recentVisits = Visit::with('apartment')
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($visit) {
                return [
                    'type' => 'visitor',
                    'message' => "Visita registrada: {$visit->visitor_name} en Apto {$visit->apartment->number}",
                    'time' => $visit->created_at->diffForHumans(),
                    'icon' => 'user-check',
                    'timestamp' => $visit->created_at,
                ];
            });

        // Actividad de transacciones contables recientes
        $recentTransactions = AccountingTransaction::where('status', 'contabilizado')
            ->orderBy('posted_at', 'desc')
            ->take(2)
            ->get()
            ->map(function ($transaction) {
                return [
                    'type' => 'accounting',
                    'message' => "Transacción contabilizada: {$transaction->description}",
                    'time' => $transaction->posted_at->diffForHumans(),
                    'icon' => 'file-text',
                    'timestamp' => $transaction->posted_at,
                ];
            });

        // Combinar todas las actividades
        $activities = $recentResidents
            ->concat($recentPayments)
            ->concat($recentVisits)
            ->concat($recentTransactions);

        // Ordenar por timestamp y tomar las más recientes
        $sortedActivities = $activities
            ->sortByDesc('timestamp')
            ->take(8)
            ->map(function ($activity) {
                unset($activity['timestamp']); // Remover timestamp para el resultado final

                return $activity;
            })
            ->values();

        if ($sortedActivities->count() > 0) {
            return $sortedActivities;
        }

        // Fallback completo a datos mock solo si no hay datos
        return collect([
            [
                'type' => 'system',
                'message' => 'Sistema iniciado - No hay actividad reciente',
                'time' => 'hace 1 hora',
                'icon' => 'activity',
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
        // Calcular dinámicamente basado en facturas del mes seleccionado
        $conjuntoConfig = ConjuntoConfig::first();
        if (! $conjuntoConfig) {
            return collect();
        }

        // Obtener todas las facturas del mes seleccionado
        $invoices = Invoice::forPeriod($year, $month)
            ->with('apartment')
            ->get();

        // Si no hay facturas para este mes, retornar vacío
        if ($invoices->isEmpty()) {
            return collect();
        }

        $alDia = 0;
        $overdue30 = 0;
        $overdue60 = 0;
        $overdue90 = 0;
        $overdue90Plus = 0;

        foreach ($invoices as $invoice) {
            if ($invoice->status === 'pagado') {
                // Factura pagada, apartamento al día
                $alDia++;
            } else {
                // Calcular días de mora basados en la fecha de vencimiento
                $today = now()->startOfDay();
                $dueDate = $invoice->due_date->startOfDay();

                if ($today->lte($dueDate)) {
                    // Aún no vencida, apartamento al día
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
            ['status' => '0-30 días de mora', 'count' => $overdue30, 'color' => '#f59e0b'],
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

    private function getCurrentMonthVisitsCount(): int
    {
        return Visit::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }

    private function getLastMonthVisitsCount(): int
    {
        $lastMonth = now()->subMonth();

        return Visit::whereMonth('created_at', $lastMonth->month)
            ->whereYear('created_at', $lastMonth->year)
            ->count();
    }

    private function getMonthlyExpenses(int $year, int $month): \Illuminate\Support\Collection
    {
        $conjunto = ConjuntoConfig::first();
        if (! $conjunto) {
            return collect();
        }

        // Verificar si hay cuentas contables configuradas
        $hasAccountingSetup = ChartOfAccounts::forConjunto($conjunto->id)->count() > 0;

        if ($hasAccountingSetup) {
            // Obtener gastos por categoría de cuentas de expense del período
            $expenseAccountCategories = [
                '5135' => ['name' => 'Servicios Públicos', 'color' => '#3b82f6'],
                '5120' => ['name' => 'Mantenimiento', 'color' => '#ef4444'],
                '5105' => ['name' => 'Seguridad', 'color' => '#10b981'],
                '5115' => ['name' => 'Aseo', 'color' => '#f59e0b'],
                '5110' => ['name' => 'Administración', 'color' => '#8b5cf6'],
                '5125' => ['name' => 'Jardinería', 'color' => '#06b6d4'],
            ];

            $startDate = "{$year}-{$month}-01";
            $endDate = "{$year}-{$month}-".now()->createFromDate($year, $month, 1)->endOfMonth()->day;

            $expenses = collect();
            $totalAmount = 0;

            foreach ($expenseAccountCategories as $accountCode => $categoryInfo) {
                $accountBalance = ChartOfAccounts::forConjunto($conjunto->id)
                    ->where('code', 'LIKE', $accountCode.'%')
                    ->where('account_type', 'expense')
                    ->get()
                    ->sum(function ($account) use ($startDate, $endDate) {
                        return $account->getBalance($startDate, $endDate);
                    });

                if ($accountBalance > 0) {
                    $expenses->push([
                        'category' => $categoryInfo['name'],
                        'amount' => $accountBalance,
                        'color' => $categoryInfo['color'],
                    ]);
                    $totalAmount += $accountBalance;
                }
            }

            // Si hay cuentas pero no transacciones, mostrar estructura preparada
            if ($expenses->isEmpty()) {
                return collect([
                    ['category' => 'Sistema contable configurado', 'amount' => 0, 'percentage' => 100, 'color' => '#10b981'],
                ]);
            }

            // Calcular porcentajes con datos reales
            return $expenses->map(function ($expense) use ($totalAmount) {
                $expense['percentage'] = round(($expense['amount'] / $totalAmount) * 100);

                return $expense;
            });
        }

        // Sin datos contables, retornar colección vacía
        return collect();
    }

    private function getPaymentTrend(): \Illuminate\Support\Collection
    {
        $conjunto = ConjuntoConfig::first();
        if (! $conjunto) {
            return collect();
        }

        // Obtener datos de los últimos 6 meses
        $months = collect();
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $year = $date->year;
            $month = $date->month;

            // Sumar pagos recibidos del mes (datos reales si existen)
            $monthlyAmount = Invoice::forPeriod($year, $month)->sum('paid_amount');

            // Agregar cuotas de acuerdos de pago
            $installmentsAmount = $this->getInstallmentsByMonth($year, $month)->sum('paid_amount');

            $monthNames = [
                1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
            ];

            $totalRealAmount = $monthlyAmount + $installmentsAmount;

            $months->push([
                'month' => sprintf('%04d-%02d', $year, $month),
                'amount' => $totalRealAmount,
                'label' => $monthNames[$month].' '.$year,
            ]);
        }

        // Si no hay datos reales de pagos, usar datos proyectados basados en apartamentos
        $hasRealPayments = $months->sum('amount') > 0;

        if (! $hasRealPayments) {
            $totalApartments = Apartment::count();
            $occupiedApartments = Apartment::where('status', 'Occupied')->count();
            $estimatedMonthlyPayment = 85000; // Estimación por apartamento ocupado

            return $months->map(function ($month, $index) use ($occupiedApartments, $estimatedMonthlyPayment) {
                // Simular variación realista en los pagos
                $variation = 0.9 + ($index * 0.02) + (rand(-5, 5) / 100); // Entre 90% y 105%
                $estimatedAmount = $occupiedApartments * $estimatedMonthlyPayment * $variation;

                return [
                    'month' => $month['month'],
                    'amount' => round($estimatedAmount),
                    'label' => $month['label'],
                ];
            });
        }

        return $months;
    }

    private function getPendingNotifications(): \Illuminate\Support\Collection
    {
        $notifications = collect();

        // Notificaciones basadas en datos reales del sistema
        $totalApartments = Apartment::count();
        $availableApartments = Apartment::where('status', 'Available')->count();
        $occupiedApartments = Apartment::where('status', 'Occupied')->count();

        // Notificación sobre apartamentos disponibles
        if ($availableApartments > 0) {
            $notifications->push([
                'id' => 'available-apartments',
                'title' => "Apartamentos disponibles para ocupar ({$availableApartments})",
                'recipients_count' => $availableApartments,
                'type' => 'Disponibilidad',
                'created_at' => now()->subHours(2),
            ]);
        }

        // Notificación sobre ocupación
        if ($totalApartments > 0) {
            $occupancyRate = round(($occupiedApartments / $totalApartments) * 100);
            if ($occupancyRate >= 90) {
                $notifications->push([
                    'id' => 'high-occupancy',
                    'title' => "Alta ocupación del conjunto ({$occupancyRate}%)",
                    'recipients_count' => $occupiedApartments,
                    'type' => 'Info',
                    'created_at' => now()->subHours(4),
                ]);
            } elseif ($occupancyRate < 70) {
                $notifications->push([
                    'id' => 'low-occupancy',
                    'title' => "Oportunidad de mercadeo - Ocupación {$occupancyRate}%",
                    'recipients_count' => $availableApartments,
                    'type' => 'Oportunidad',
                    'created_at' => now()->subHours(6),
                ]);
            }
        }

        $maintenanceApartments = Apartment::where('status', 'Maintenance')->count();
        if ($maintenanceApartments > 0) {
            $notifications->push([
                'id' => 'maintenance-apartments',
                'title' => "Apartamentos en mantenimiento ({$maintenanceApartments})",
                'recipients_count' => $maintenanceApartments,
                'type' => 'Mantenimiento',
                'created_at' => now()->subHours(8),
            ]);
        }

        // Notificación sobre sistema contable
        $hasChartOfAccounts = ChartOfAccounts::count() > 0;
        if ($hasChartOfAccounts) {
            $notifications->push([
                'id' => 'accounting-ready',
                'title' => 'Sistema contable configurado y listo',
                'recipients_count' => ChartOfAccounts::count(),
                'type' => 'Sistema',
                'created_at' => now()->subHours(12),
            ]);
        } else {
            $notifications->push([
                'id' => 'accounting-setup-needed',
                'title' => 'Configurar sistema contable',
                'recipients_count' => 0,
                'type' => 'Configuración',
                'created_at' => now()->subMinutes(30),
            ]);
        }

        // Si no hay notificaciones, crear una de estado
        if ($notifications->isEmpty()) {
            $notifications->push([
                'id' => 'system-status',
                'title' => 'Conjunto configurado - Sistema operativo',
                'recipients_count' => $totalApartments,
                'type' => 'Info',
                'created_at' => now()->subHours(1),
            ]);
        }

        return $notifications->take(5); // Limitar a 5 notificaciones máximas
    }

    private function getAccountingSystemReadiness(): array
    {
        $conjunto = ConjuntoConfig::first();

        if (! $conjunto) {
            return [
                'has_apartments' => false,
                'has_chart_of_accounts' => false,
                'has_payment_concepts' => false,
                'has_accounting_mappings' => false,
                'is_ready' => false,
                'needs_wizard' => false,
            ];
        }

        $hasApartments = Apartment::where('conjunto_config_id', $conjunto->id)->exists();
        $hasChartOfAccounts = ChartOfAccounts::where('conjunto_config_id', $conjunto->id)->exists();
        $hasPaymentConcepts = PaymentConcept::exists();
        $hasAccountingMappings = PaymentConceptAccountMapping::exists();

        $isReady = $hasApartments && $hasChartOfAccounts && $hasPaymentConcepts && $hasAccountingMappings;

        // Only show wizard if conjunto is configured but accounting is not
        $needsWizard = $conjunto->exists && $hasApartments && ! $isReady;

        return [
            'has_apartments' => $hasApartments,
            'has_chart_of_accounts' => $hasChartOfAccounts,
            'has_payment_concepts' => $hasPaymentConcepts,
            'has_accounting_mappings' => $hasAccountingMappings,
            'is_ready' => $isReady,
            'needs_wizard' => $needsWizard,
        ];
    }
}
