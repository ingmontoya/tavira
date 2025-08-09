<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\Resident;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        // Get resident and apartment information
        $resident = $user->resident;
        $apartment = $resident?->apartment;
        $conjuntoConfig = ConjuntoConfig::first();

        if (! $resident || ! $apartment) {
            return redirect()->route('dashboard')->with('error', 'No se encontró información del residente o apartamento.');
        }

        // Get account status for this apartment
        $accountStatus = $this->getAccountStatus($apartment);

        // Get next payment information
        $nextPayment = $this->getNextPayment($apartment);

        // Get payment status
        $paymentStatus = $this->getPaymentStatus($apartment);

        // Get communications/announcements (mock data for now)
        $communications = $this->getCommunications();

        // Get visits information (mock data for now)
        $visits = $this->getVisits($apartment);

        // Get package information (mock data for now)
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
        $totalInvoiced = $apartment->invoices()
            ->whereIn('status', ['pending', 'overdue', 'partial'])
            ->sum('total_amount');

        $totalPaid = $apartment->invoices()
            ->whereIn('status', ['pending', 'overdue', 'partial'])
            ->sum('paid_amount');

        $balance = $totalPaid - $totalInvoiced;

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
            return [
                'amount' => $nextInvoice->total_amount - $nextInvoice->paid_amount,
                'dueDate' => $nextInvoice->due_date->format('d M Y'),
                'description' => $nextInvoice->description ?? 'Pago de administración',
            ];
        }

        return [
            'amount' => 0,
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
        // Mock data for communications - replace with real data later
        return [
            [
                'id' => 1,
                'title' => 'Corte de agua programado',
                'content' => 'Se realizará mantenimiento en las redes de agua el próximo sábado de 8:00 AM a 2:00 PM. Se recomienda almacenar agua suficiente.',
                'date' => now()->subDays(1)->toISOString(),
                'isNew' => true,
            ],
            [
                'id' => 2,
                'title' => 'Nueva política de visitantes',
                'content' => 'A partir del próximo mes, todos los visitantes deberán registrarse con documento de identidad en la recepción.',
                'date' => now()->subDays(3)->toISOString(),
                'isNew' => false,
            ],
            [
                'id' => 3,
                'title' => 'Asamblea general',
                'content' => 'Se convoca a asamblea general para el día 15 del mes en curso a las 7:00 PM en el salón comunal.',
                'date' => now()->subDays(5)->toISOString(),
                'isNew' => false,
            ],
        ];
    }

    private function getVisits(Apartment $apartment): array
    {
        // Mock data for visits - replace with real data later
        return [
            [
                'id' => 1,
                'visitorName' => 'Carlos Rodríguez',
                'purpose' => 'Visita familiar',
                'date' => now()->addDays(1)->format('d M Y'),
                'time' => '14:00',
                'status' => 'Autorizada',
            ],
            [
                'id' => 2,
                'visitorName' => 'María González',
                'purpose' => 'Técnico de internet',
                'date' => now()->format('d M Y'),
                'time' => '10:30',
                'status' => 'Completada',
            ],
            [
                'id' => 3,
                'visitorName' => 'Pedro Martínez',
                'purpose' => 'Domicilio',
                'date' => now()->addDays(2)->format('d M Y'),
                'time' => '18:00',
                'status' => 'Pendiente',
            ],
        ];
    }

    private function getPackages(Apartment $apartment): array
    {
        // Mock data for packages - replace with real data later
        return [
            [
                'id' => 1,
                'sender' => 'Amazon',
                'description' => 'Paquete pequeño',
                'receivedDate' => now()->subDays(1)->toISOString(),
                'status' => 'Pendiente',
            ],
            [
                'id' => 2,
                'sender' => 'MercadoLibre',
                'description' => 'Electrodoméstico',
                'receivedDate' => now()->subDays(3)->toISOString(),
                'status' => 'Entregado',
            ],
        ];
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
                    'date' => $invoice->created_at->toISOString(),
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
}
