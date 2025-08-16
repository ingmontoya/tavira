<?php

namespace App\Http\Controllers;

use App\Models\ConjuntoConfig;
use App\Models\MaintenanceStaff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceStaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view_maintenance_staff')->only(['index', 'show']);
        $this->middleware('permission:create_maintenance_staff')->only(['create', 'store']);
        $this->middleware('permission:edit_maintenance_staff')->only(['edit', 'update']);
        $this->middleware('permission:delete_maintenance_staff')->only(['destroy']);
    }

    public function index(): Response
    {
        $conjuntoConfig = ConjuntoConfig::first();

        $staff = MaintenanceStaff::where('conjunto_config_id', $conjuntoConfig->id)
            ->orderBy('name')
            ->get();

        return Inertia::render('Maintenance/Staff/Index', [
            'staff' => $staff,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Maintenance/Staff/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $conjuntoConfig = ConjuntoConfig::first();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'specialties' => 'array',
            'specialties.*' => 'string|max:100',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_internal' => 'boolean',
            'availability_schedule' => 'nullable|array',
        ]);

        $validated['conjunto_config_id'] = $conjuntoConfig->id;

        MaintenanceStaff::create($validated);

        return redirect()->route('maintenance-staff.index')
            ->with('success', 'Personal de mantenimiento agregado exitosamente.');
    }

    public function show(MaintenanceStaff $maintenanceStaff): Response
    {
        $maintenanceStaff->load(['maintenanceRequests.maintenanceCategory']);

        return Inertia::render('Maintenance/Staff/Show', [
            'staff' => $maintenanceStaff,
        ]);
    }

    public function edit(MaintenanceStaff $maintenanceStaff): Response
    {
        return Inertia::render('Maintenance/Staff/Edit', [
            'staff' => $maintenanceStaff,
        ]);
    }

    public function update(Request $request, MaintenanceStaff $maintenanceStaff): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'specialties' => 'array',
            'specialties.*' => 'string|max:100',
            'hourly_rate' => 'nullable|numeric|min:0',
            'is_internal' => 'boolean',
            'is_active' => 'boolean',
            'availability_schedule' => 'nullable|array',
        ]);

        $maintenanceStaff->update($validated);

        return redirect()->route('maintenance-staff.index')
            ->with('success', 'Personal de mantenimiento actualizado exitosamente.');
    }

    public function destroy(MaintenanceStaff $maintenanceStaff): RedirectResponse
    {
        if ($maintenanceStaff->maintenanceRequests()->count() > 0) {
            return back()->with('error', 'No se puede eliminar este personal porque tiene solicitudes asignadas.');
        }

        $maintenanceStaff->delete();

        return redirect()->route('maintenance-staff.index')
            ->with('success', 'Personal de mantenimiento eliminado exitosamente.');
    }
}
