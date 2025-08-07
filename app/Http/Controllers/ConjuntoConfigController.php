<?php

namespace App\Http\Controllers;

use App\Models\ConjuntoConfig;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ConjuntoConfigController extends Controller
{
    /**
     * Display the conjunto configuration.
     */
    public function index()
    {
        $conjunto = ConjuntoConfig::with(['apartmentTypes', 'apartments'])
            ->withCount(['apartmentTypes', 'apartments'])
            ->first();

        return Inertia::render('ConjuntoConfig/Index', [
            'conjunto' => $conjunto,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new conjunto configuration.
     */
    public function create()
    {
        // Check if a conjunto already exists (single conjunto system)
        $existingConjunto = ConjuntoConfig::first();

        if ($existingConjunto) {
            return redirect()->route('conjunto-config.index')
                ->with('error', 'Ya existe una configuración de conjunto. Solo se permite una configuración por sistema.');
        }

        return Inertia::render('ConjuntoConfig/Create');
    }

    /**
     * Store a newly created conjunto configuration.
     */
    public function store(Request $request)
    {
        // Check if a conjunto already exists (single conjunto system)
        $existingConjunto = ConjuntoConfig::first();

        if ($existingConjunto) {
            return redirect()->route('conjunto-config.index')
                ->with('error', 'Ya existe una configuración de conjunto. Solo se permite una configuración por sistema.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:conjunto_configs,name',
            'description' => 'nullable|string',
            'number_of_towers' => 'required|integer|min:1|max:20',
            'floors_per_tower' => 'required|integer|min:1|max:50',
            'apartments_per_floor' => 'required|integer|min:1|max:10',
            'tower_names' => 'nullable|array',
            'tower_names.*' => 'string|max:10',
            'configuration_metadata' => 'nullable|array',
            'apartment_types' => 'required|array|min:1',
            'apartment_types.*.name' => 'required|string|max:255',
            'apartment_types.*.description' => 'nullable|string',
            'apartment_types.*.area_sqm' => 'required|numeric|min:1',
            'apartment_types.*.bedrooms' => 'required|integer|min:0',
            'apartment_types.*.bathrooms' => 'required|integer|min:0',
            'apartment_types.*.has_balcony' => 'boolean',
            'apartment_types.*.has_laundry_room' => 'boolean',
            'apartment_types.*.has_maid_room' => 'boolean',
            'apartment_types.*.coefficient' => 'required|numeric|min:0|max:1',
            'apartment_types.*.administration_fee' => 'required|numeric|min:0',
            'apartment_types.*.floor_positions' => 'required|array|min:1',
            'apartment_types.*.floor_positions.*' => 'integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Create conjunto configuration (excluding apartment_types)
            $configData = collect($validated)->except(['apartment_types'])->toArray();
            $configData['is_active'] = true; // New conjuntos are active by default

            $conjunto = ConjuntoConfig::create($configData);

            // Create apartment types
            foreach ($validated['apartment_types'] as $typeData) {
                $conjunto->apartmentTypes()->create($typeData);
            }

            // Generate apartments automatically
            $conjunto->generateApartments();

            DB::commit();

            return redirect()->route('conjunto-config.index')
                ->with('success', 'Conjunto creado exitosamente con '.$conjunto->estimated_apartments_count.' apartamentos generados.');

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors(['error' => 'Error al crear el conjunto: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Show the form for editing the conjunto configuration.
     */
    public function edit()
    {
        $conjunto = ConjuntoConfig::with('apartmentTypes')->first();

        if (! $conjunto) {
            return redirect()->route('conjunto-config.create')
                ->with('error', 'No se encontró configuración del conjunto. Primero debe crear uno.');
        }

        // Ensure is_active is properly cast to boolean
        $conjunto->makeHidden(['created_at', 'updated_at']);
        $conjunto->is_active = (bool) $conjunto->is_active;

        return Inertia::render('ConjuntoConfig/Edit', [
            'conjunto' => $conjunto,
            'flash' => [
                'success' => session('success'),
                'error' => session('error'),
            ],
        ]);
    }

    /**
     * Update the conjunto configuration.
     */
    public function update(Request $request)
    {
        $conjunto = ConjuntoConfig::first();

        if (! $conjunto) {
            return back()->withErrors(['error' => 'No se encontró la configuración del conjunto.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'number_of_towers' => 'required|integer|min:1|max:20',
            'floors_per_tower' => 'required|integer|min:1|max:50',
            'apartments_per_floor' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
            'tower_names' => 'nullable|array',
            'tower_names.*' => 'string|max:10',
            'configuration_metadata' => 'nullable|array',
            'apartment_types' => 'nullable|array',
            'apartment_types.*.name' => 'required|string|max:255',
            'apartment_types.*.description' => 'nullable|string',
            'apartment_types.*.area_sqm' => 'required|numeric|min:1',
            'apartment_types.*.bedrooms' => 'required|integer|min:0',
            'apartment_types.*.bathrooms' => 'required|integer|min:0',
            'apartment_types.*.has_balcony' => 'boolean',
            'apartment_types.*.has_laundry_room' => 'boolean',
            'apartment_types.*.has_maid_room' => 'boolean',
            'apartment_types.*.coefficient' => 'required|numeric|min:0|max:1',
            'apartment_types.*.administration_fee' => 'required|numeric|min:0',
            'apartment_types.*.floor_positions' => 'nullable|array',
            'apartment_types.*.floor_positions.*' => 'integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            // Update conjunto configuration (excluding apartment_types)
            $configData = collect($validated)->except(['apartment_types'])->toArray();

            // Ensure is_active is properly cast to boolean
            if (isset($configData['is_active'])) {
                $configData['is_active'] = (bool) $configData['is_active'];
            }

            $conjunto->update($configData);

            // Handle apartment types if provided
            if (isset($validated['apartment_types'])) {
                // Delete existing apartment types for this conjunto
                $conjunto->apartmentTypes()->delete();

                // Create new apartment types
                foreach ($validated['apartment_types'] as $typeData) {
                    $conjunto->apartmentTypes()->create($typeData);
                }
            }

            DB::commit();

            return redirect()->route('conjunto-config.index')
                ->with('success', 'Configuración del conjunto actualizada exitosamente');

        } catch (\Exception $e) {
            DB::rollback();

            return back()->withErrors(['error' => 'Error al actualizar la configuración: '.$e->getMessage()]);
        }
    }

    /**
     * Generate apartments based on current configuration.
     */
    public function generateApartments()
    {
        $conjunto = ConjuntoConfig::first();

        if (! $conjunto) {
            return back()->withErrors(['error' => 'No se encontró la configuración del conjunto.']);
        }

        try {
            if (! $conjunto->canGenerateApartments()) {
                return back()->withErrors(['error' => 'No se pueden generar apartamentos. Asegúrate de tener al menos un tipo de apartamento definido.']);
            }

            // Generate apartments
            $conjunto->generateApartments();

            $totalGenerated = $conjunto->estimated_apartments_count;

            return back()->with('success', 'Se generaron apartamentos exitosamente según la configuración actual');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al generar apartamentos: '.$e->getMessage()]);
        }
    }

    /**
     * Show detailed view of the conjunto configuration.
     */
    public function show()
    {
        $conjunto = ConjuntoConfig::with([
            'apartmentTypes',
            'apartments.apartmentType',
            'apartments.invoices',
        ])->first();

        if (! $conjunto) {
            return redirect()->route('conjunto-config.edit');
        }

        // Group apartments by tower and floor for better visualization
        $apartmentsByTower = $conjunto->apartments
            ->groupBy('tower')
            ->map(function ($apartments) {
                return $apartments->groupBy('floor');
            });

        // Calculate estimated monthly income (sum of all apartment monthly fees)
        $estimatedMonthlyIncome = $conjunto->apartments->sum(function ($apartment) {
            // Use apartment's monthly_fee if set, otherwise use apartment type's administration_fee
            $fee = $apartment->monthly_fee ?? ($apartment->apartmentType ? $apartment->apartmentType->administration_fee ?? 0 : 0);

            return $fee;
        });

        // Calculate real monthly income (sum of payments received this month)
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        $realMonthlyIncome = \App\Models\Invoice::where('status', 'paid')
            ->whereMonth('paid_date', $currentMonth)
            ->whereYear('paid_date', $currentYear)
            ->sum('paid_amount');

        return Inertia::render('ConjuntoConfig/Show', [
            'conjunto' => $conjunto,
            'apartmentsByTower' => $apartmentsByTower,
            'statistics' => [
                'total_apartments' => $conjunto->apartments->count(),
                'occupied_apartments' => $conjunto->apartments->where('status', 'Occupied')->count(),
                'available_apartments' => $conjunto->apartments->where('status', 'Available')->count(),
                'maintenance_apartments' => $conjunto->apartments->where('status', 'Maintenance')->count(),
                'estimated_monthly_income' => $estimatedMonthlyIncome,
                'real_monthly_income' => $realMonthlyIncome,
            ],
        ]);
    }
}
