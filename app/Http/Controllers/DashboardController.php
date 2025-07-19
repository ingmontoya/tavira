<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Resident;
use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\ApartmentType;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // Obtener datos reales
        $totalResidents = Resident::count();
        $totalApartments = Apartment::count();
        $totalConjuntos = ConjuntoConfig::count();

        // Calcular crecimiento de residentes (mock data si no hay suficientes datos)
        $currentMonthResidents = Resident::whereMonth('created_at', now()->month)->count();
        $lastMonthResidents = Resident::whereMonth('created_at', now()->subMonth()->month)->count();
        $residentGrowth = $this->calculateGrowthPercentage($currentMonthResidents, $lastMonthResidents) ?: 15.2;

        // KPIs - Combinando datos reales con mockeados
        $kpis = [
            'totalResidents' => max($totalResidents, 347), // Mínimo de demo
            'totalApartments' => max($totalApartments, 182), // Mínimo de demo
            'pendingPayments' => 23, // Mock data - implementar después
            'monthlyVisitors' => 1247, // Mock data - implementar después
            'residentGrowth' => $residentGrowth,
            'visitorGrowth' => 8.3, // Mock data
        ];

        // Residentes por Torre - Combinando datos reales con mock
        $residentsByTower = $this->getResidentsByTower();

        // Estados de pago - Mock data
        $paymentsByStatus = collect([
            ['status' => 'Al día', 'count' => 156, 'color' => '#10b981'],
            ['status' => 'Vencido 1-30 días', 'count' => 23, 'color' => '#f59e0b'],
            ['status' => 'Vencido 31-60 días', 'count' => 8, 'color' => '#ef4444'],
            ['status' => 'Vencido +60 días', 'count' => 3, 'color' => '#7f1d1d'],
        ]);

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
                    'color' => $this->generateColor($index)
                ];
            })->values();
        }

        // Fallback a datos mock si no hay datos reales
        return collect([
            ['name' => 'Torre 1 - Vista Hermosa', 'residents' => 89, 'color' => '#3b82f6'],
            ['name' => 'Torre 2 - Vista Hermosa', 'residents' => 76, 'color' => '#ef4444'],
            ['name' => 'Torre 3 - Vista Hermosa', 'residents' => 65, 'color' => '#10b981'],
            ['name' => 'Torre 1 - Parque Real', 'residents' => 54, 'color' => '#f59e0b'],
            ['name' => 'Torre 2 - Parque Real', 'residents' => 63, 'color' => '#8b5cf6'],
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
                'Reserved' => '#8b5cf6'
            ];

            $statusLabels = [
                'Occupied' => 'Ocupados',
                'Available' => 'Disponibles',
                'Maintenance' => 'Mantenimiento',
                'Reserved' => 'Reservados'
            ];

            return $realData->map(function ($item) use ($statusColors, $statusLabels) {
                return [
                    'status' => $statusLabels[$item->status] ?? $item->status,
                    'count' => $item->count,
                    'color' => $statusColors[$item->status] ?? '#6b7280'
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
                    'message' => "Nuevo residente: {$resident->first_name} {$resident->last_name} en Apto " . (isset($resident->apartment->number) ? $resident->apartment->number : 'N/A'),
                    'time' => $resident->created_at->diffForHumans(),
                    'icon' => 'user-plus'
                ];
            });

        if ($recentResidents->count() > 0) {
            // Combinar con algunos datos mock adicionales
            $mockActivity = collect([
                [
                    'type' => 'payment',
                    'message' => 'Pago recibido: Apto 301 - Administración Julio',
                    'time' => 'hace 15 minutos',
                    'icon' => 'dollar-sign'
                ],
                [
                    'type' => 'visitor',
                    'message' => 'Visita registrada: María González en Torre 2',
                    'time' => 'hace 1 hora',
                    'icon' => 'user-check'
                ],
                [
                    'type' => 'maintenance',
                    'message' => 'Solicitud de mantenimiento: Ascensor Torre 1',
                    'time' => 'hace 2 horas',
                    'icon' => 'wrench'
                ],
                [
                    'type' => 'communication',
                    'message' => 'Comunicado enviado: Corte de agua programado',
                    'time' => 'hace 3 horas',
                    'icon' => 'bell'
                ]
            ]);

            return $recentResidents->concat($mockActivity)->take(8)->values();
        }

        // Fallback completo a datos mock
        return collect([
            [
                'type' => 'resident',
                'message' => 'Nuevo residente: Ana García en Apto 405',
                'time' => 'hace 5 minutos',
                'icon' => 'user-plus'
            ],
            [
                'type' => 'payment',
                'message' => 'Pago recibido: Apto 301 - Administración Julio',
                'time' => 'hace 15 minutos',
                'icon' => 'dollar-sign'
            ],
            [
                'type' => 'visitor',
                'message' => 'Visita registrada: Carlos Rodríguez en Torre 2',
                'time' => 'hace 45 minutos',
                'icon' => 'user-check'
            ],
            [
                'type' => 'maintenance',
                'message' => 'Solicitud de mantenimiento: Ascensor Torre 1',
                'time' => 'hace 1 hora',
                'icon' => 'wrench'
            ],
            [
                'type' => 'communication',
                'message' => 'Comunicado enviado: Asamblea general',
                'time' => 'hace 2 horas',
                'icon' => 'bell'
            ],
            [
                'type' => 'resident',
                'message' => 'Nuevo residente: Pedro Martínez en Apto 102',
                'time' => 'hace 3 horas',
                'icon' => 'user-plus'
            ],
            [
                'type' => 'payment',
                'message' => 'Pago recibido: Apto 205 - Administración Julio',
                'time' => 'hace 4 horas',
                'icon' => 'dollar-sign'
            ],
            [
                'type' => 'visitor',
                'message' => 'Visita registrada: Laura Fernández en Torre 3',
                'time' => 'hace 5 horas',
                'icon' => 'user-check'
            ]
        ]);
    }

    private function generateColor($id)
    {
        $colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'];
        return $colors[$id % count($colors)];
    }
}
