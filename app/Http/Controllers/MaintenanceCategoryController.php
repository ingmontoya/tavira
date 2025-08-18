<?php

namespace App\Http\Controllers;

use App\Models\ConjuntoConfig;
use App\Models\MaintenanceCategory;
use Database\Seeders\MaintenanceCategorySeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceCategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_maintenance_categories')->only(['index', 'show']);
        $this->middleware('permission:create_maintenance_categories')->only(['create', 'store', 'seedCategories']);
        $this->middleware('permission:edit_maintenance_categories')->only(['edit', 'update']);
        $this->middleware('permission:delete_maintenance_categories')->only(['destroy']);
    }

    public function index(): Response
    {
        $conjuntoConfig = ConjuntoConfig::first();

        $categories = MaintenanceCategory::where('conjunto_config_id', $conjuntoConfig->id)
            ->withCount('maintenanceRequests')
            ->orderBy('name')
            ->get();

        return Inertia::render('Maintenance/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Maintenance/Categories/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $conjuntoConfig = ConjuntoConfig::first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'priority_level' => 'required|integer|between:1,4',
            'requires_approval' => 'boolean',
            'estimated_hours' => 'nullable|numeric|min:0',
        ]);

        $validated['conjunto_config_id'] = $conjuntoConfig->id;

        MaintenanceCategory::create($validated);

        return redirect()->route('maintenance-categories.index')
            ->with('success', 'Categoría de mantenimiento creada exitosamente.');
    }

    public function show(MaintenanceCategory $maintenanceCategory): Response
    {
        $maintenanceCategory->load(['maintenanceRequests.requestedBy']);

        return Inertia::render('Maintenance/Categories/Show', [
            'category' => $maintenanceCategory,
        ]);
    }

    public function edit(MaintenanceCategory $maintenanceCategory): Response
    {
        return Inertia::render('Maintenance/Categories/Edit', [
            'category' => $maintenanceCategory,
        ]);
    }

    public function update(Request $request, MaintenanceCategory $maintenanceCategory): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'color' => 'required|string|max:7',
            'priority_level' => 'required|integer|between:1,4',
            'requires_approval' => 'boolean',
            'estimated_hours' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
        ]);

        $maintenanceCategory->update($validated);

        return redirect()->route('maintenance-categories.index')
            ->with('success', 'Categoría de mantenimiento actualizada exitosamente.');
    }

    public function destroy(MaintenanceCategory $maintenanceCategory): RedirectResponse
    {
        if ($maintenanceCategory->maintenanceRequests()->count() > 0) {
            return back()->with('error', 'No se puede eliminar una categoría que tiene solicitudes asociadas.');
        }

        $maintenanceCategory->delete();

        return redirect()->route('maintenance-categories.index')
            ->with('success', 'Categoría de mantenimiento eliminada exitosamente.');
    }

    public function seedCategories(): RedirectResponse
    {
        try {
            $conjuntoConfig = ConjuntoConfig::first();

            if (! $conjuntoConfig) {
                return redirect()->route('maintenance-categories.index')
                    ->with('error', 'No se encontró configuración de conjunto. Configure primero el conjunto residencial.');
            }

            $seeder = new MaintenanceCategorySeeder;
            $seeder->run();

            return redirect()->route('maintenance-categories.index')
                ->with('success', 'Categorías de mantenimiento predeterminadas creadas exitosamente.');
        } catch (\Exception $e) {
            return redirect()->route('maintenance-categories.index')
                ->with('error', 'Error al crear las categorías: '.$e->getMessage());
        }
    }
}
