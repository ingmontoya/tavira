<?php

namespace App\Http\Controllers;

use App\Exports\DelinquentApartmentsExport;
use App\Models\Apartment;
use App\Models\ApartmentType;
use App\Models\ConjuntoConfig;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ApartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Apartment::with(['apartmentType', 'conjuntoConfig', 'residents', 'paymentAgreements' => function ($query) {
            $query->whereIn('status', ['active', 'approved', 'pending_approval']);
        }]);

        // Apply filters
        if ($request->filled('search')) {
            $query->where('number', 'like', '%'.$request->search.'%');
        }

        if ($request->filled('tower')) {
            $query->where('tower', $request->tower);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('apartment_type_id')) {
            $query->where('apartment_type_id', $request->apartment_type_id);
        }

        if ($request->filled('floor')) {
            $query->where('floor', $request->floor);
        }

        $apartments = $query->orderBy('tower')
            ->orderBy('floor')
            ->orderBy('position_on_floor')
            ->paginate(20)
            ->withQueryString();

        // Append payment status badge and agreement info to each apartment
        $apartments->getCollection()->transform(function ($apartment) {
            $apartment->payment_status_badge = $apartment->paymentStatusBadge;
            
            // Get active payment agreement info
            $activeAgreement = $apartment->paymentAgreements
                ->whereIn('status', ['active', 'approved', 'pending_approval'])
                ->first();
            
            if ($activeAgreement) {
                $apartment->has_payment_agreement = true;
                $apartment->payment_agreement_status = $activeAgreement->status;
                $apartment->payment_agreement_badge = $activeAgreement->status_badge;
            } else {
                $apartment->has_payment_agreement = false;
                $apartment->payment_agreement_status = null;
                $apartment->payment_agreement_badge = null;
            }

            return $apartment;
        });

        // Payment statistics
        $totalApartments = Apartment::count();
        $currentPayments = Apartment::where('payment_status', 'current')->count();
        $overdue30 = Apartment::where('payment_status', 'overdue_30')->count();
        $overdue60 = Apartment::where('payment_status', 'overdue_60')->count();
        $overdue90 = Apartment::where('payment_status', 'overdue_90')->count();
        $overdue90Plus = Apartment::where('payment_status', 'overdue_90_plus')->count();
        $totalDelinquent = $overdue30 + $overdue60 + $overdue90 + $overdue90Plus;
        
        // Payment agreement statistics
        $apartmentsWithAgreements = Apartment::whereHas('paymentAgreements', function($query) {
            $query->whereIn('status', ['active', 'approved', 'pending_approval']);
        })->count();

        $paymentStats = [
            'total' => $totalApartments,
            'current' => $currentPayments,
            'overdue_30' => $overdue30,
            'overdue_60' => $overdue60,
            'overdue_90' => $overdue90,
            'overdue_90_plus' => $overdue90Plus,
            'total_delinquent' => $totalDelinquent,
            'with_agreements' => $apartmentsWithAgreements,
            'current_percentage' => $totalApartments > 0 ? round(($currentPayments / $totalApartments) * 100, 1) : 0,
            'delinquent_percentage' => $totalApartments > 0 ? round(($totalDelinquent / $totalApartments) * 100, 1) : 0,
            'agreements_percentage' => $totalApartments > 0 ? round(($apartmentsWithAgreements / $totalApartments) * 100, 1) : 0,
        ];

        // Get filter options
        $conjunto = ConjuntoConfig::first();
        $apartmentTypes = ApartmentType::all();
        $towers = Apartment::distinct()->pluck('tower')->sort()->values();
        $floors = Apartment::distinct()->pluck('floor')->sort()->values();
        $statuses = ['Available', 'Occupied', 'Maintenance', 'Reserved'];

        return Inertia::render('apartments/Index', [
            'apartments' => $apartments,
            'filters' => $request->only(['search', 'tower', 'status', 'apartment_type_id', 'floor']),
            'conjunto' => $conjunto,
            'apartmentTypes' => $apartmentTypes,
            'towers' => $towers,
            'floors' => $floors,
            'statuses' => $statuses,
            'paymentStats' => $paymentStats,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $conjunto = ConjuntoConfig::first();
        $apartmentTypes = ApartmentType::all();
        $statuses = ['Available', 'Occupied', 'Maintenance', 'Reserved'];

        return Inertia::render('apartments/Create', [
            'conjunto' => $conjunto,
            'apartmentTypes' => $apartmentTypes,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'apartment_type_id' => 'required|exists:apartment_types,id',
            'number' => 'required|string|max:10',
            'tower' => 'required|string|max:10',
            'floor' => 'required|integer|min:1',
            'position_on_floor' => 'required|integer|min:1',
            'status' => 'required|in:Available,Occupied,Maintenance,Reserved',
            'monthly_fee' => 'required|numeric|min:0',
            'utilities' => 'nullable|array',
            'features' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        // Add the conjunto_config_id automatically
        $validated['conjunto_config_id'] = ConjuntoConfig::first()->id;

        // Check if apartment number already exists in the conjunto
        $existingApartment = Apartment::where('conjunto_config_id', $validated['conjunto_config_id'])
            ->where('number', $validated['number'])
            ->first();

        if ($existingApartment) {
            return back()->withErrors(['number' => 'Ya existe un apartamento con este número en el conjunto.']);
        }

        Apartment::create($validated);

        return redirect()->route('apartments.index')
            ->with('success', 'Apartamento creado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Apartment $apartment)
    {
        $apartment->load(['apartmentType', 'conjuntoConfig', 'residents', 'paymentAgreements']);

        return Inertia::render('apartments/Show', [
            'apartment' => $apartment,
            'statistics' => [
                'total_residents' => $apartment->residents->count(),
                'active_residents' => $apartment->residents->where('status', 'Active')->count(),
                'owners' => $apartment->residents->where('resident_type', 'Owner')->count(),
                'tenants' => $apartment->residents->where('resident_type', 'Tenant')->count(),
            ],
            'paymentAgreements' => $apartment->paymentAgreements,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Apartment $apartment)
    {
        $conjunto = ConjuntoConfig::first();
        $apartmentTypes = ApartmentType::all();
        $statuses = ['Available', 'Occupied', 'Maintenance', 'Reserved'];

        return Inertia::render('apartments/Edit', [
            'apartment' => $apartment,
            'conjunto' => $conjunto,
            'apartmentTypes' => $apartmentTypes,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Apartment $apartment)
    {
        $validated = $request->validate([
            'apartment_type_id' => 'required|exists:apartment_types,id',
            'number' => 'required|string|max:10',
            'tower' => 'required|string|max:10',
            'floor' => 'required|integer|min:1',
            'position_on_floor' => 'required|integer|min:1',
            'status' => 'required|in:Available,Occupied,Maintenance,Reserved',
            'monthly_fee' => 'required|numeric|min:0',
            'utilities' => 'nullable|array',
            'features' => 'nullable|array',
            'notes' => 'nullable|string',
        ]);

        // Add the conjunto_config_id automatically
        $validated['conjunto_config_id'] = $apartment->conjunto_config_id;

        // Check if apartment number already exists in the conjunto (excluding current apartment)
        $existingApartment = Apartment::where('conjunto_config_id', $validated['conjunto_config_id'])
            ->where('number', $validated['number'])
            ->where('id', '!=', $apartment->id)
            ->first();

        if ($existingApartment) {
            return back()->withErrors(['number' => 'Ya existe un apartamento con este número en el conjunto.']);
        }

        $apartment->update($validated);

        return redirect()->route('apartments.index')
            ->with('success', 'Apartamento actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Apartment $apartment)
    {
        // Check if apartment has residents
        if ($apartment->residents()->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar un apartamento que tiene residentes asignados.']);
        }

        $apartment->delete();

        return redirect()->route('apartments.index')
            ->with('success', 'Apartamento eliminado exitosamente');
    }

    /**
     * Display delinquent apartments grouped by tower
     */
    public function delinquent()
    {
        $delinquentApartments = Apartment::with(['apartmentType', 'residents'])
            ->delinquent()
            ->orderBy('tower')
            ->orderBy('payment_status')
            ->orderBy('floor')
            ->orderBy('position_on_floor')
            ->get()
            ->map(function ($apartment) {
                $apartment->payment_status_badge = $apartment->paymentStatusBadge;

                return $apartment;
            })
            ->groupBy('tower');

        $stats = [
            'total_delinquent' => Apartment::delinquent()->count(),
            'overdue_30' => Apartment::byPaymentStatus('overdue_30')->count(),
            'overdue_60' => Apartment::byPaymentStatus('overdue_60')->count(),
            'overdue_90' => Apartment::byPaymentStatus('overdue_90')->count(),
            'overdue_90_plus' => Apartment::byPaymentStatus('overdue_90_plus')->count(),
        ];

        return Inertia::render('apartments/Delinquent', [
            'delinquentApartments' => $delinquentApartments,
            'stats' => $stats,
            'cutoffDate' => now()->subMonth()->endOfMonth()->format('Y-m-d'),
        ]);
    }

    /**
     * Export delinquent apartments to Excel
     */
    public function exportDelinquentExcel()
    {
        $filename = 'morosos_'.now()->format('Y-m-d_H-i-s').'.xlsx';

        return Excel::download(new DelinquentApartmentsExport, $filename);
    }

    /**
     * Export delinquent apartments to PDF
     */
    public function exportDelinquentPdf()
    {
        $delinquentApartments = Apartment::with(['apartmentType', 'residents'])
            ->delinquent()
            ->orderBy('tower')
            ->orderBy('payment_status')
            ->orderBy('floor')
            ->orderBy('position_on_floor')
            ->get()
            ->map(function ($apartment) {
                $apartment->payment_status_badge = $apartment->paymentStatusBadge;

                return $apartment;
            })
            ->groupBy('tower');

        $stats = [
            'total_delinquent' => Apartment::delinquent()->count(),
            'overdue_30' => Apartment::byPaymentStatus('overdue_30')->count(),
            'overdue_60' => Apartment::byPaymentStatus('overdue_60')->count(),
            'overdue_90' => Apartment::byPaymentStatus('overdue_90')->count(),
            'overdue_90_plus' => Apartment::byPaymentStatus('overdue_90_plus')->count(),
        ];

        $pdf = Pdf::loadView('exports.delinquent-apartments-pdf', [
            'delinquentApartments' => $delinquentApartments,
            'stats' => $stats,
            'cutoffDate' => now()->subMonth()->endOfMonth()->format('Y-m-d'),
            'generatedAt' => now()->format('d/m/Y H:i:s'),
        ]);

        $filename = 'morosos_'.now()->format('Y-m-d_H-i-s').'.pdf';

        return $pdf->download($filename);
    }
}
