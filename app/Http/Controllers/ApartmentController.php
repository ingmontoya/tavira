<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\ApartmentType;
use App\Models\ConjuntoConfig;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ApartmentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Apartment::with(['apartmentType', 'conjuntoConfig', 'residents']);

        // If user is individual, only show apartments from their conjunto
        if ($user->isIndividual() && $user->conjunto_config_id) {
            $query->where('conjunto_config_id', $user->conjunto_config_id);
        }

        // Apply filters
        if ($request->filled('search')) {
            $query->where('number', 'like', '%' . $request->search . '%');
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

        // Get filter options
        $conjuntoConfigs = ConjuntoConfig::all();
        if ($user->isIndividual() && $user->conjunto_config_id) {
            $conjuntoConfigs = $conjuntoConfigs->where('id', $user->conjunto_config_id);
        }

        $apartmentTypes = ApartmentType::all();

        // Get towers and floors only from user's conjunto
        $towerQuery = Apartment::distinct();
        $floorQuery = Apartment::distinct();

        if ($user->isIndividual() && $user->conjunto_config_id) {
            $towerQuery->where('conjunto_config_id', $user->conjunto_config_id);
            $floorQuery->where('conjunto_config_id', $user->conjunto_config_id);
        }

        $towers = $towerQuery->pluck('tower')->sort()->values();
        $floors = $floorQuery->pluck('floor')->sort()->values();
        $statuses = ['Available', 'Occupied', 'Maintenance', 'Reserved'];

        return Inertia::render('apartments/Index', [
            'apartments' => $apartments,
            'filters' => $request->only(['search', 'tower', 'status', 'apartment_type_id', 'floor']),
            'conjuntoConfigs' => $conjuntoConfigs,
            'apartmentTypes' => $apartmentTypes,
            'towers' => $towers,
            'floors' => $floors,
            'statuses' => $statuses,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();

        $conjuntoConfigs = ConjuntoConfig::all();
        if ($user->isIndividual() && $user->conjunto_config_id) {
            $conjuntoConfigs = $conjuntoConfigs->where('id', $user->conjunto_config_id);
        }

        $apartmentTypes = ApartmentType::all();
        $statuses = ['Available', 'Occupied', 'Maintenance', 'Reserved'];

        return Inertia::render('apartments/Create', [
            'conjuntoConfigs' => $conjuntoConfigs,
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
            'conjunto_config_id' => 'required|exists:conjunto_configs,id',
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

        // Check if apartment number already exists in the same conjunto
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
        $apartment->load(['apartmentType', 'conjuntoConfig', 'residents']);

        return Inertia::render('apartments/Show', [
            'apartment' => $apartment,
            'statistics' => [
                'total_residents' => $apartment->residents->count(),
                'active_residents' => $apartment->residents->where('status', 'Active')->count(),
                'owners' => $apartment->residents->where('resident_type', 'Owner')->count(),
                'tenants' => $apartment->residents->where('resident_type', 'Tenant')->count(),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Apartment $apartment)
    {
        $user = Auth::user();

        $conjuntoConfigs = ConjuntoConfig::all();
        if ($user->isIndividual() && $user->conjunto_config_id) {
            $conjuntoConfigs = $conjuntoConfigs->where('id', $user->conjunto_config_id);
        }

        $apartmentTypes = ApartmentType::all();
        $statuses = ['Available', 'Occupied', 'Maintenance', 'Reserved'];

        return Inertia::render('apartments/Edit', [
            'apartment' => $apartment,
            'conjuntoConfigs' => $conjuntoConfigs,
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
            'conjunto_config_id' => 'required|exists:conjunto_configs,id',
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

        // Check if apartment number already exists in the same conjunto (excluding current apartment)
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
     * Get apartments by conjunto for API calls
     */
    public function getByConjunto(ConjuntoConfig $conjuntoConfig)
    {
        $apartments = $conjuntoConfig->apartments()
            ->with('apartmentType')
            ->orderBy('tower')
            ->orderBy('floor')
            ->orderBy('position_on_floor')
            ->get();

        return response()->json($apartments);
    }
}
