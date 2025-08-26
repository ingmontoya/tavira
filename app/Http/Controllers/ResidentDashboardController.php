<?php

namespace App\Http\Controllers;

use App\Http\Resources\DashboardResource;
use App\Models\Announcement;
use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\Correspondence;
use App\Models\Resident;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ResidentDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Check if user has resident or propietario role
        if (! $user->hasRole(['residente', 'propietario'])) {
            return redirect()->route('dashboard')->with('error', 'Acceso denegado. Este dashboard es solo para residentes y propietarios.');
        }

        // Get apartment information
        $apartment = $user->apartment;
        $conjuntoConfig = ConjuntoConfig::first();

        if (! $apartment) {
            // Show a specific page for residents without apartment instead of redirecting back
            return Inertia::render('residents/NoApartment', [
                'conjuntoName' => $conjuntoConfig?->name ?? 'Tu Conjunto',
                'message' => 'Aún no tienes un apartamento asignado. Por favor contacta al administrador.',
            ]);
        }

        // Get account status for this apartment
        $accountStatus = $this->getAccountStatus($apartment);

        // Get next payment information
        $nextPayment = $this->getNextPayment($apartment);

        // Get payment status
        $paymentStatus = $this->getPaymentStatus($apartment);

        // Get communications/announcements (real data)
        $communications = $this->getCommunications();

        // Get visits information (real data)
        $visits = $this->getVisits($apartment);

        // Get package information (real data)
        $packages = $this->getPackages($apartment);

        // Get recent account transactions
        $accountTransactions = $this->getAccountTransactions($apartment);

        return Inertia::render('residents/Dashboard', [
            'accountStatus' => $accountStatus,
            'apartment' => [
                'id' => $apartment->id,
                'number' => $apartment->number,
                'tower' => $apartment->tower,
            ],
            'conjuntoName' => $conjuntoConfig?->name ?? 'Tu Conjunto',
            'nextPayment' => $nextPayment,
            'paymentStatus' => $paymentStatus,
            'communications' => $communications,
            'visits' => $visits,
            'packages' => $packages,
            'accountTransactions' => $accountTransactions,
        ]);
    }

    private function getAccountStatus(Apartment $apartment): array
    {
        // Calculate current balance based on invoices and payments
        $totalInvoiced = (float) $apartment->invoices()
            ->whereIn('status', ['pending', 'overdue', 'partial'])
            ->sum('total_amount');

        $totalPaid = (float) $apartment->invoices()
            ->whereIn('status', ['pending', 'overdue', 'partial'])
            ->sum('paid_amount');

        $balance = $totalInvoiced - $totalPaid; // Pending amount (positive = owes money)

        return [
            'balance' => $balance,
            'totalInvoiced' => $totalInvoiced,
            'totalPaid' => $totalPaid,
        ];
    }

    private function getNextPayment(Apartment $apartment): array
    {
        $nextInvoice = $apartment->invoices()
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->first();

        if ($nextInvoice) {
            $pendingAmount = (float) $nextInvoice->total_amount - (float) ($nextInvoice->paid_amount ?? 0);

            return [
                'amount' => max(0, $pendingAmount), // Ensure non-negative
                'dueDate' => $nextInvoice->due_date->format('d M Y'),
                'description' => $nextInvoice->description ?? 'Pago de administración',
            ];
        }

        return [
            'amount' => 0.0,
            'dueDate' => 'Sin pagos pendientes',
            'description' => '',
        ];
    }

    private function getPaymentStatus(Apartment $apartment): array
    {
        $oldestUnpaidInvoice = $apartment->invoices()
            ->whereIn('status', ['pending', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->first();

        if (! $oldestUnpaidInvoice) {
            return [
                'status' => 'al_dia',
                'daysOverdue' => 0,
                'daysUntilDue' => 0,
            ];
        }

        $today = Carbon::now()->startOfDay();
        $dueDate = $oldestUnpaidInvoice->due_date->startOfDay();

        if ($today->lte($dueDate)) {
            $daysUntilDue = $today->diffInDays($dueDate);
            if ($daysUntilDue <= 5) {
                return [
                    'status' => 'proximo_vencimiento',
                    'daysOverdue' => 0,
                    'daysUntilDue' => $daysUntilDue,
                ];
            }

            return [
                'status' => 'al_dia',
                'daysOverdue' => 0,
                'daysUntilDue' => $daysUntilDue,
            ];
        } else {
            $daysOverdue = $dueDate->diffInDays($today);

            return [
                'status' => 'vencido',
                'daysOverdue' => $daysOverdue,
                'daysUntilDue' => 0,
            ];
        }
    }

    private function getCommunications(): array
    {
        $userId = Auth::id();

        $announcements = Announcement::with(['createdBy'])
            ->forUser($userId)
            ->take(5)
            ->get()
            ->map(function ($announcement) use ($userId) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'content' => $announcement->content,
                    'date' => $announcement->published_at?->toISOString() ?? $announcement->created_at?->toISOString() ?? now()->toISOString(),
                    'isNew' => ! $announcement->isReadBy($userId),
                ];
            })
            ->toArray();

        return $announcements;
    }

    private function getVisits(Apartment $apartment): array
    {
        $visits = Visit::where('apartment_id', $apartment->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($visit) {
                $statusMap = [
                    'pending' => 'Pendiente',
                    'active' => 'Autorizada',
                    'used' => 'Completada',
                    'expired' => 'Expirada',
                    'cancelled' => 'Cancelada',
                ];

                return [
                    'id' => $visit->id,
                    'visitorName' => $visit->visitor_name,
                    'purpose' => $visit->visit_reason ?? 'Visita general',
                    'date' => $visit->valid_from->format('d M Y'),
                    'time' => $visit->valid_from->format('H:i'),
                    'status' => $statusMap[$visit->status] ?? ucfirst($visit->status),
                ];
            })
            ->toArray();

        return $visits;
    }

    private function getPackages(Apartment $apartment): array
    {
        $packages = Correspondence::where('apartment_id', $apartment->id)
            ->orderBy('received_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($correspondence) {
                $statusMap = [
                    'received' => 'Pendiente',
                    'delivered' => 'Entregado',
                ];

                return [
                    'id' => $correspondence->id,
                    'sender' => $correspondence->sender_company ?? $correspondence->sender_name,
                    'description' => $correspondence->description ?? ucfirst($correspondence->type),
                    'receivedDate' => $correspondence->received_at?->toISOString() ?? $correspondence->created_at?->toISOString() ?? now()->toISOString(),
                    'status' => $statusMap[$correspondence->status] ?? ucfirst($correspondence->status),
                ];
            })
            ->toArray();

        return $packages;
    }

    private function getAccountTransactions(Apartment $apartment): array
    {
        // Get recent invoices and payments
        $transactions = collect();

        // Recent invoices
        $recentInvoices = $apartment->invoices()
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => 'invoice_'.$invoice->id,
                    'type' => 'invoice',
                    'description' => $invoice->description ?? 'Factura de administración',
                    'amount' => $invoice->total_amount,
                    'date' => $invoice->created_at?->toISOString() ?? now()->toISOString(),
                ];
            });

        $transactions = $transactions->concat($recentInvoices);

        // Recent payments (mock data for now)
        $mockPayments = collect([
            [
                'id' => 'payment_1',
                'type' => 'payment',
                'description' => 'Pago administración Julio 2024',
                'amount' => 350000,
                'date' => now()->subDays(5)->toISOString(),
            ],
            [
                'id' => 'payment_2',
                'type' => 'payment',
                'description' => 'Pago administración Junio 2024',
                'amount' => 350000,
                'date' => now()->subDays(35)->toISOString(),
            ],
        ]);

        $transactions = $transactions->concat($mockPayments);

        // Sort by date descending and take latest 6
        return $transactions->sortByDesc('date')->take(6)->values()->all();
    }

    /**
     * API endpoint for mobile app dashboard
     */
    public function apiIndex(Request $request)
    {
        $user = $request->user();

        // Check if user has resident or propietario role
        if (! $user->hasRole(['residente', 'propietario'])) {
            return response()->json([
                'error' => 'Acceso denegado. Este dashboard es solo para residentes y propietarios.',
            ], 403);
        }

        // Get apartment information
        $apartment = $user->apartment;

        if (! $apartment) {
            return response()->json([
                'error' => 'No se encontró información del apartamento asignado.',
            ], 404);
        }

        // Load necessary relationships
        $user->load(['resident.apartment.apartmentType']);
        $apartment->load(['apartmentType', 'invoices', 'payments']);

        // Get account status and payment info
        $accountStatus = $this->getAccountStatus($apartment);
        $nextPayment = $this->getNextPayment($apartment);

        // Compile dashboard data
        $dashboardData = [
            'user' => $user,
            'apartment' => $apartment,
            'current_balance' => (float) ($accountStatus['balance'] ?? 0),
            'next_payment_amount' => (float) ($nextPayment['amount'] ?? 0),
            'next_payment_due' => $nextPayment['dueDate'] ?? null,
            'unread_notifications' => $user->unreadNotifications()->count(),
            'pending_visits' => Visit::where('apartment_id', $apartment->id)
                ->where('status', 'pending')
                ->count(),
            'open_maintenance_requests' => 0, // Will implement when maintenance model is available
            'unread_announcements' => Announcement::forUser($user->id)
                ->whereDoesntHave('confirmations', function ($query) use ($user) {
                    $query->where('user_id', $user->id)->whereNotNull('read_at');
                })
                ->count(),
            'payments_this_year' => $apartment->payments()
                ->whereYear('payment_date', now()->year)
                ->count(),
            'visits_this_month' => Visit::where('apartment_id', $apartment->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
            'last_payment_date' => $apartment->payments()
                ->latest('payment_date')
                ->first()?->payment_date,
            'last_payment_amount' => $apartment->payments()
                ->latest('payment_date')
                ->first()?->amount ?? 0,
            'latest_invoices' => $apartment->invoices()
                ->with('items.paymentConcept')
                ->latest()
                ->take(3)
                ->get(),
            'recent_payments' => $apartment->payments()
                ->with('applications.invoice')
                ->latest('payment_date')
                ->take(3)
                ->get(),
            'latest_announcements' => Announcement::forUser($user->id)
                ->with('createdBy')
                ->latest()
                ->take(3)
                ->get(),
            'recent_visits' => Visit::where('apartment_id', $apartment->id)
                ->latest()
                ->take(3)
                ->get(),
        ];

        return new DashboardResource($dashboardData);
    }
}
