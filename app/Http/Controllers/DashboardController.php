<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Student;
use App\Models\Program;
use App\Models\Subject;
use App\Models\Group;
use App\Models\Enrollment;
use App\Models\Period;
use App\Models\StudyPlan;
use App\Models\AcademicHistory;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        // KPIs Principales - Demo Data
        $totalStudents = 1247;
        $totalPrograms = 8;
        $totalSubjects = 156;
        $totalEnrollments = 2891;
        
        // Estudiantes por programa - Demo Data
        $studentsByProgram = collect([
            ['name' => 'Ingeniería de Sistemas', 'students' => 387, 'color' => '#3b82f6'],
            ['name' => 'Administración de Empresas', 'students' => 298, 'color' => '#ef4444'],
            ['name' => 'Psicología', 'students' => 234, 'color' => '#10b981'],
            ['name' => 'Derecho', 'students' => 156, 'color' => '#f59e0b'],
            ['name' => 'Contaduría Pública', 'students' => 98, 'color' => '#8b5cf6'],
            ['name' => 'Medicina', 'students' => 74, 'color' => '#06b6d4'],
        ]);

        // Matriculas por estado - Demo Data
        $enrollmentsByStatus = collect([
            ['status' => 'Matriculados', 'count' => 2156, 'color' => '#3b82f6'],
            ['status' => 'Completados', 'count' => 523, 'color' => '#10b981'],
            ['status' => 'Retirados', 'count' => 156, 'color' => '#6b7280'],
            ['status' => 'Reprobados', 'count' => 56, 'color' => '#ef4444'],
        ]);

        // Progreso académico general - Demo Data
        $academicProgress = collect([
            ['range' => '0-25%', 'count' => 89, 'color' => '#ef4444'],
            ['range' => '26-50%', 'count' => 234, 'color' => '#f59e0b'],
            ['range' => '51-75%', 'count' => 456, 'color' => '#3b82f6'],
            ['range' => '76-100%', 'count' => 468, 'color' => '#10b981'],
        ]);

        // Capacidad de grupos - Demo Data
        $groupsCapacity = collect([
            ['name' => 'Algoritmos y Programación - Grupo A', 'usage' => 95.5, 'enrolled' => 42, 'capacity' => 44, 'color' => '#ef4444'],
            ['name' => 'Bases de Datos - Grupo B', 'usage' => 88.9, 'enrolled' => 32, 'capacity' => 36, 'color' => '#f59e0b'],
            ['name' => 'Cálculo Diferencial - Grupo C', 'usage' => 82.5, 'enrolled' => 33, 'capacity' => 40, 'color' => '#f59e0b'],
            ['name' => 'Física I - Grupo A', 'usage' => 75.0, 'enrolled' => 30, 'capacity' => 40, 'color' => '#f59e0b'],
            ['name' => 'Inglés Técnico - Grupo D', 'usage' => 68.8, 'enrolled' => 22, 'capacity' => 32, 'color' => '#10b981'],
            ['name' => 'Química General - Grupo B', 'usage' => 62.5, 'enrolled' => 25, 'capacity' => 40, 'color' => '#10b981'],
            ['name' => 'Estadística - Grupo A', 'usage' => 55.6, 'enrolled' => 20, 'capacity' => 36, 'color' => '#10b981'],
            ['name' => 'Redes de Computadores - Grupo C', 'usage' => 45.8, 'enrolled' => 11, 'capacity' => 24, 'color' => '#10b981'],
        ]);

        // Periodos activos - Demo Data
        $activePeriods = collect([
            (object)[
                'id' => 1,
                'name' => 'Semestre 2024-2',
                'start_date' => '2024-08-01',
                'end_date' => '2024-12-15',
                'subjects_count' => 89,
                'enrollments_count' => 1456
            ],
            (object)[
                'id' => 2,
                'name' => 'Intersemestral 2024',
                'start_date' => '2024-12-16',
                'end_date' => '2025-01-31',
                'subjects_count' => 23,
                'enrollments_count' => 234
            ]
        ]);

        // Tendencia de matrículas por mes - Demo Data
        $enrollmentTrend = collect([
            ['month' => '2024-02', 'count' => 342, 'label' => 'Feb 2024'],
            ['month' => '2024-03', 'count' => 389, 'label' => 'Mar 2024'],
            ['month' => '2024-04', 'count' => 298, 'label' => 'Abr 2024'],
            ['month' => '2024-05', 'count' => 445, 'label' => 'May 2024'],
            ['month' => '2024-06', 'count' => 512, 'label' => 'Jun 2024'],
            ['month' => '2024-07', 'count' => 678, 'label' => 'Jul 2024'],
        ]);

        return Inertia::render('Dashboard', [
            'kpis' => [
                'totalStudents' => $totalStudents,
                'totalPrograms' => $totalPrograms,
                'totalSubjects' => $totalSubjects,
                'totalEnrollments' => $totalEnrollments,
                'studentGrowth' => 12.5, // Demo: 12.5% growth
                'enrollmentGrowth' => 8.7, // Demo: 8.7% growth
            ],
            'charts' => [
                'studentsByProgram' => $studentsByProgram,
                'enrollmentsByStatus' => $enrollmentsByStatus,
                'academicProgress' => $academicProgress,
                'groupsCapacity' => $groupsCapacity->take(10), // Top 10 grupos
                'enrollmentTrend' => $enrollmentTrend,
            ],
            'activePeriods' => $activePeriods,
            'recentActivity' => $this->getDemoRecentActivity(),
        ]);
    }

    private function generateColor($id)
    {
        $colors = ['#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6', '#06b6d4', '#84cc16', '#f97316'];
        return $colors[$id % count($colors)];
    }

    private function getStatusLabel($status)
    {
        $labels = [
            'enrolled' => 'Matriculados',
            'completed' => 'Completados',
            'dropped' => 'Retirados',
            'failed' => 'Reprobados'
        ];
        return $labels[$status] ?? $status;
    }

    private function getStatusColor($status)
    {
        $colors = [
            'enrolled' => '#3b82f6',
            'completed' => '#10b981',
            'dropped' => '#6b7280',
            'failed' => '#ef4444'
        ];
        return $colors[$status] ?? '#6b7280';
    }

    private function getAcademicProgress()
    {
        $students = Student::all();
        $progressRanges = [
            '0-25%' => 0,
            '26-50%' => 0,
            '51-75%' => 0,
            '76-100%' => 0,
        ];

        foreach ($students as $student) {
            $progress = $student->total_credits > 0 ? ($student->credits_completed / $student->total_credits) * 100 : 0;
            
            if ($progress <= 25) $progressRanges['0-25%']++;
            elseif ($progress <= 50) $progressRanges['26-50%']++;
            elseif ($progress <= 75) $progressRanges['51-75%']++;
            else $progressRanges['76-100%']++;
        }

        return collect($progressRanges)->map(function ($count, $range) {
            return [
                'range' => $range,
                'count' => $count,
                'color' => $this->getProgressColor($range)
            ];
        })->values();
    }

    private function getProgressColor($range)
    {
        $colors = [
            '0-25%' => '#ef4444',
            '26-50%' => '#f59e0b',
            '51-75%' => '#3b82f6',
            '76-100%' => '#10b981',
        ];
        return $colors[$range];
    }

    private function getStudentGrowthPercentage()
    {
        $currentMonth = Student::whereMonth('created_at', now()->month)->count();
        $lastMonth = Student::whereMonth('created_at', now()->subMonth()->month)->count();
        
        if ($lastMonth == 0) return $currentMonth > 0 ? 100 : 0;
        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    private function getEnrollmentGrowthPercentage()
    {
        $currentMonth = Enrollment::whereMonth('enrollment_date', now()->month)->count();
        $lastMonth = Enrollment::whereMonth('enrollment_date', now()->subMonth()->month)->count();
        
        if ($lastMonth == 0) return $currentMonth > 0 ? 100 : 0;
        return round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1);
    }

    private function getRecentActivity()
    {
        $recentEnrollments = Enrollment::with(['student', 'group.subject'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'type' => 'enrollment',
                    'message' => $enrollment->student->first_name . ' ' . $enrollment->student->last_name . ' se matriculó en ' . $enrollment->group->subject->name,
                    'time' => $enrollment->created_at->diffForHumans(),
                    'icon' => 'user-plus'
                ];
            });

        $recentStudents = Student::orderBy('created_at', 'desc')
            ->take(3)
            ->get()
            ->map(function ($student) {
                return [
                    'type' => 'student',
                    'message' => 'Nuevo estudiante registrado: ' . $student->first_name . ' ' . $student->last_name,
                    'time' => $student->created_at->diffForHumans(),
                    'icon' => 'user'
                ];
            });

        return $recentEnrollments->concat($recentStudents)
            ->sortByDesc('time')
            ->take(8)
            ->values();
    }

    private function getDemoRecentActivity()
    {
        return collect([
            [
                'type' => 'enrollment',
                'message' => 'María García se matriculó en Algoritmos y Programación',
                'time' => 'hace 2 minutos',
                'icon' => 'user-plus'
            ],
            [
                'type' => 'student',
                'message' => 'Nuevo estudiante registrado: Carlos Rodríguez',
                'time' => 'hace 15 minutos',
                'icon' => 'user'
            ],
            [
                'type' => 'enrollment',
                'message' => 'Ana López se matriculó en Bases de Datos',
                'time' => 'hace 23 minutos',
                'icon' => 'user-plus'
            ],
            [
                'type' => 'grade',
                'message' => 'Calificaciones actualizadas para Cálculo Diferencial',
                'time' => 'hace 1 hora',
                'icon' => 'book-open'
            ],
            [
                'type' => 'enrollment',
                'message' => 'Pedro Martínez se matriculó en Física I',
                'time' => 'hace 2 horas',
                'icon' => 'user-plus'
            ],
            [
                'type' => 'student',
                'message' => 'Nuevo estudiante registrado: Laura Fernández',
                'time' => 'hace 3 horas',
                'icon' => 'user'
            ],
            [
                'type' => 'group',
                'message' => 'Nuevo grupo creado para Inglés Técnico',
                'time' => 'hace 4 horas',
                'icon' => 'users'
            ],
            [
                'type' => 'enrollment',
                'message' => 'José Silva se matriculó en Estadística',
                'time' => 'hace 5 horas',
                'icon' => 'user-plus'
            ]
        ]);
    }
}
