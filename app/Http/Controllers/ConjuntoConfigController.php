<?php

namespace App\Http\Controllers;

use App\Models\ConjuntoConfig;
use App\Models\ApartmentType;
use App\Models\Apartment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ConjuntoConfigController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        
        $query = ConjuntoConfig::with(['apartmentTypes', 'apartments'])
            ->withCount(['apartmentTypes', 'apartments']);
            
        // If user is individual, only show their conjunto
        if ($user->isIndividual() && $user->conjunto_config_id) {
            $query->where('id', $user->conjunto_config_id);
        }
        
        $conjuntos = $query->get();

        return Inertia::render('ConjuntoConfig/Index', [
            'conjuntos' => $conjuntos,
            'canCreateNew' => $user->canManageMultipleConjuntos() || !$user->conjunto_config_id
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = Auth::user();
        
        // Check if user can create another conjunto
        if ($user->isIndividual() && $user->conjunto_config_id) {
            return redirect()->route('conjunto-config.index')
                ->withErrors(['error' => 'Los usuarios individuales solo pueden manejar un conjunto.']);
        }
        
        return Inertia::render('ConjuntoConfig/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:conjunto_configs',
            'description' => 'nullable|string',
            'number_of_towers' => 'required|integer|min:1|max:20',
            'floors_per_tower' => 'required|integer|min:1|max:50',
            'apartments_per_floor' => 'required|integer|min:1|max:10',
            'tower_names' => 'nullable|array',
            'tower_names.*' => 'string|max:10',
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
            'apartment_types.*.floor_positions' => 'required|array',
            'apartment_types.*.floor_positions.*' => 'integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $user = Auth::user();
            
            // Check if user can create another conjunto
            if ($user->isIndividual() && $user->conjunto_config_id) {
                throw new \Exception('Los usuarios individuales solo pueden manejar un conjunto.');
            }
            
            $conjuntoConfig = ConjuntoConfig::create($validated);

            // If individual user, assign this conjunto to them
            if ($user->isIndividual()) {
                $user->update(['conjunto_config_id' => $conjuntoConfig->id]);
            }

            // Create apartment types
            foreach ($validated['apartment_types'] as $typeData) {
                $conjuntoConfig->apartmentTypes()->create($typeData);
            }

            // Generate apartments based on configuration
            $conjuntoConfig->generateApartments();

            DB::commit();

            return redirect()->route('conjunto-config.index')
                ->with('success', 'Configuración del conjunto creada exitosamente');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Error al crear la configuración: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ConjuntoConfig $conjuntoConfig)
    {
        $conjuntoConfig->load(['apartmentTypes', 'apartments.apartmentType']);
        
        // Group apartments by tower and floor for better visualization
        $apartmentsByTower = $conjuntoConfig->apartments
            ->groupBy('tower')
            ->map(function ($apartments) {
                return $apartments->groupBy('floor');
            });

        return Inertia::render('ConjuntoConfig/Show', [
            'conjuntoConfig' => $conjuntoConfig,
            'apartmentsByTower' => $apartmentsByTower,
            'statistics' => [
                'total_apartments' => $conjuntoConfig->apartments->count(),
                'occupied_apartments' => $conjuntoConfig->apartments->where('status', 'Occupied')->count(),
                'available_apartments' => $conjuntoConfig->apartments->where('status', 'Available')->count(),
                'maintenance_apartments' => $conjuntoConfig->apartments->where('status', 'Maintenance')->count(),
                'total_area' => $conjuntoConfig->apartmentTypes->sum(function ($type) {
                    return $type->area_sqm * $type->apartments->count();
                }),
                'monthly_fees_total' => $conjuntoConfig->apartments->sum('monthly_fee'),
            ]
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ConjuntoConfig $conjuntoConfig)
    {
        $conjuntoConfig->load('apartmentTypes');
        
        return Inertia::render('ConjuntoConfig/Edit', [
            'conjuntoConfig' => $conjuntoConfig
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ConjuntoConfig $conjuntoConfig)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:conjunto_configs,name,' . $conjuntoConfig->id,
            'description' => 'nullable|string',
            'number_of_towers' => 'required|integer|min:1|max:20',
            'floors_per_tower' => 'required|integer|min:1|max:50',
            'apartments_per_floor' => 'required|integer|min:1|max:10',
            'is_active' => 'boolean',
            'tower_names' => 'nullable|array',
            'tower_names.*' => 'string|max:10',
            'apartment_types' => 'required|array|min:1',
            'apartment_types.*.id' => 'sometimes|exists:apartment_types,id',
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
        ]);

        try {
            DB::beginTransaction();

            $conjuntoConfig->update($validated);

            // Update apartment types
            foreach ($validated['apartment_types'] as $typeData) {
                if (isset($typeData['id'])) {
                    $conjuntoConfig->apartmentTypes()->find($typeData['id'])->update($typeData);
                } else {
                    $conjuntoConfig->apartmentTypes()->create($typeData);
                }
            }

            DB::commit();

            return redirect()->route('conjunto-config.index')
                ->with('success', 'Configuración del conjunto actualizada exitosamente');

        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Error al actualizar la configuración: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ConjuntoConfig $conjuntoConfig)
    {
        try {
            $conjuntoConfig->delete();
            
            return redirect()->route('conjunto-config.index')
                ->with('success', 'Configuración del conjunto eliminada exitosamente');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar la configuración: ' . $e->getMessage()]);
        }
    }

    /**
     * Generate apartments for a specific conjunto configuration
     */
    public function generateApartments(ConjuntoConfig $conjuntoConfig)
    {
        try {
            if (!$conjuntoConfig->canGenerateApartments()) {
                return back()->withErrors(['error' => 'No se pueden generar apartamentos. Asegúrate de tener al menos un tipo de apartamento definido.']);
            }
            
            // Generate apartments
            $conjuntoConfig->generateApartments();
            
            $totalGenerated = $conjuntoConfig->estimated_apartments_count;
            
            return back()->with('success', "Se generaron {$totalGenerated} apartamentos exitosamente");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al generar apartamentos: ' . $e->getMessage()]);
        }
    }
}
