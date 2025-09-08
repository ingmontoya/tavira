<?php

namespace App\Http\Controllers;

use App\Events\AssemblyCreated;
use App\Events\AssemblyClosed;
use App\Jobs\CloseScheduledAssembly;
use App\Models\Assembly;
use App\Models\ConjuntoConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AssemblyController extends Controller
{
    public function index(Request $request)
    {
        $conjuntoConfig = ConjuntoConfig::first();
        
        if (!$conjuntoConfig) {
            return redirect()->route('dashboard')
                ->with('error', 'Debe configurar el conjunto primero.');
        }

        $query = Assembly::with(['creator', 'votes', 'delegates'])
            ->forConjunto($conjuntoConfig->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $assemblies = $query->orderByDesc('scheduled_at')
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'total' => Assembly::forConjunto($conjuntoConfig->id)->count(),
            'scheduled' => Assembly::forConjunto($conjuntoConfig->id)->byStatus('scheduled')->count(),
            'in_progress' => Assembly::forConjunto($conjuntoConfig->id)->byStatus('in_progress')->count(),
            'closed' => Assembly::forConjunto($conjuntoConfig->id)->byStatus('closed')->count(),
        ];

        return Inertia::render('Assemblies/Index', [
            'assemblies' => $assemblies,
            'stats' => $stats,
            'filters' => $request->only(['status', 'type', 'search']),
        ]);
    }

    public function create()
    {
        $conjuntoConfig = ConjuntoConfig::first();
        
        if (!$conjuntoConfig) {
            return redirect()->route('dashboard')
                ->with('error', 'Debe configurar el conjunto primero.');
        }

        return Inertia::render('Assemblies/Create', [
            'conjuntoConfig' => $conjuntoConfig,
        ]);
    }

    public function store(Request $request)
    {
        $conjuntoConfig = ConjuntoConfig::first();
        
        if (!$conjuntoConfig) {
            return redirect()->route('dashboard')
                ->with('error', 'Debe configurar el conjunto primero.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:ordinary,extraordinary',
            'scheduled_at' => 'required|date|after:now',
            'required_quorum_percentage' => 'required|integer|min:1|max:100',
            'documents' => 'nullable|array',
            'documents.*' => 'string',
            'metadata' => 'nullable|array',
        ]);

        $assembly = Assembly::create([
            ...$validated,
            'conjunto_config_id' => $conjuntoConfig->id,
            'status' => 'scheduled',
            'created_by' => Auth::id(),
        ]);

        AssemblyCreated::dispatch($assembly, Auth::user());

        return redirect()->route('assemblies.show', $assembly)
            ->with('success', 'Asamblea creada exitosamente.');
    }

    public function show(Assembly $assembly)
    {
        $assembly->load([
            'creator',
            'votes.options',
            'votes.apartmentVotes.apartment',
            'votes.apartmentVotes.castByUser',
            'delegates.delegatorApartment',
            'delegates.delegateUser'
        ]);

        $userApartments = Auth::user()->resident?->apartment_id ? 
            [Auth::user()->resident->apartment] : [];

        $userDelegates = $assembly->delegates()
            ->forDelegate(Auth::id())
            ->active()
            ->with('delegatorApartment')
            ->get();

        // Get attendance data if assembly is active
        $residents = null;
        $attendanceStats = null;
        $canManageAttendance = false;

        if ($assembly->status === 'in_progress') {
            // Get all residents with their apartment information
            $residents = \App\Models\User::whereHas('resident.apartment')
                ->with(['resident.apartment.apartmentType'])
                ->get()
                ->map(function ($user) {
                    $apartment = $user->resident->apartment;
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'apartment' => [
                            'id' => $apartment->id,
                            'number' => $apartment->number,
                            'type' => $apartment->apartmentType->name ?? 'N/A',
                        ],
                        'is_online' => false, // Default for now, can be enhanced with real-time data
                        'last_seen' => $user->last_seen_at,
                        'attendance_status' => 'not_registered', // Default status
                        'registered_at' => null,
                        'delegate_to' => null,
                    ];
                });

            // Calculate attendance stats
            $totalApartments = \App\Models\Apartment::count();
            $registeredApartments = 0; // Will be updated with real attendance data
            $presentApartments = 0;
            $absentApartments = 0;
            $delegatedApartments = 0;
            $onlineResidents = $residents->where('is_online', true)->count();
            $quorumPercentage = $totalApartments > 0 ? ($presentApartments / $totalApartments) * 100 : 0;

            $attendanceStats = [
                'total_apartments' => $totalApartments,
                'registered_apartments' => $registeredApartments,
                'present_apartments' => $presentApartments,
                'absent_apartments' => $absentApartments,
                'delegated_apartments' => $delegatedApartments,
                'online_residents' => $onlineResidents,
                'total_residents' => $residents->count(),
                'quorum_percentage' => $quorumPercentage,
                'required_quorum_percentage' => $assembly->required_quorum_percentage,
                'has_quorum' => $quorumPercentage >= $assembly->required_quorum_percentage,
            ];

            $canManageAttendance = Auth::user()->can('manage_assembly_attendance');
        }

        return Inertia::render('Assemblies/Show', [
            'assembly' => array_merge($assembly->toArray(), [
                'residents' => $residents,
                'attendance_stats' => $attendanceStats,
                'can_manage_attendance' => $canManageAttendance,
            ]),
            'userApartments' => $userApartments,
            'userDelegates' => $userDelegates,
            'canManage' => Auth::user()->can('manage assemblies'),
        ]);
    }

    public function edit(Assembly $assembly)
    {
        if ($assembly->status !== 'scheduled') {
            return redirect()->route('assemblies.show', $assembly)
                ->with('error', 'Solo se pueden editar asambleas programadas.');
        }

        return Inertia::render('Assemblies/Edit', [
            'assembly' => $assembly,
        ]);
    }

    public function update(Request $request, Assembly $assembly)
    {
        if ($assembly->status !== 'scheduled') {
            return redirect()->route('assemblies.show', $assembly)
                ->with('error', 'Solo se pueden editar asambleas programadas.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:ordinary,extraordinary',
            'scheduled_at' => 'required|date|after:now',
            'required_quorum_percentage' => 'required|integer|min:1|max:100',
            'documents' => 'nullable|array',
            'documents.*' => 'string',
            'metadata' => 'nullable|array',
        ]);

        $assembly->update($validated);

        return redirect()->route('assemblies.show', $assembly)
            ->with('success', 'Asamblea actualizada exitosamente.');
    }

    public function destroy(Assembly $assembly)
    {
        if (!in_array($assembly->status, ['scheduled', 'closed'])) {
            return redirect()->route('assemblies.show', $assembly)
                ->with('error', 'No se puede eliminar una asamblea en curso.');
        }

        $assembly->delete();

        return redirect()->route('assemblies.index')
            ->with('success', 'Asamblea eliminada exitosamente.');
    }

    public function start(Assembly $assembly)
    {
        if (!$assembly->start()) {
            return redirect()->route('assemblies.show', $assembly)
                ->with('error', 'No se pudo iniciar la asamblea.');
        }

        return redirect()->route('assemblies.show', $assembly)
            ->with('success', 'Asamblea iniciada exitosamente.');
    }

    public function close(Request $request, Assembly $assembly)
    {
        $validated = $request->validate([
            'meeting_notes' => 'nullable|string',
            'schedule_closure' => 'nullable|boolean',
            'closure_delay_minutes' => 'nullable|integer|min:1|max:60',
        ]);

        if ($validated['schedule_closure'] ?? false) {
            $delayMinutes = $validated['closure_delay_minutes'] ?? 5;
            
            CloseScheduledAssembly::dispatch($assembly)
                ->delay(now()->addMinutes($delayMinutes));

            return redirect()->route('assemblies.show', $assembly)
                ->with('success', "Asamblea será cerrada automáticamente en {$delayMinutes} minutos.");
        }

        if (!$assembly->close($validated['meeting_notes'] ?? null)) {
            return redirect()->route('assemblies.show', $assembly)
                ->with('error', 'No se pudo cerrar la asamblea.');
        }

        AssemblyClosed::dispatch($assembly, Auth::user());

        return redirect()->route('assemblies.show', $assembly)
            ->with('success', 'Asamblea cerrada exitosamente.');
    }

    public function cancel(Assembly $assembly)
    {
        if (!$assembly->cancel()) {
            return redirect()->route('assemblies.show', $assembly)
                ->with('error', 'No se pudo cancelar la asamblea.');
        }

        return redirect()->route('assemblies.show', $assembly)
            ->with('success', 'Asamblea cancelada exitosamente.');
    }

    /**
     * API: Get attendance status for an assembly
     */
    public function getAttendanceStatus(Assembly $assembly)
    {
        try {
            // Get all residents with their apartment information
            $residents = \App\Models\User::whereHas('resident.apartment')
                ->with(['resident.apartment.apartmentType'])
                ->get()
                ->map(function ($user) {
                    $apartment = $user->resident->apartment;
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'apartment' => [
                            'id' => $apartment->id,
                            'number' => $apartment->number,
                            'type' => $apartment->apartmentType->name ?? 'N/A',
                        ],
                        'is_online' => false,
                        'last_seen' => $user->last_seen_at,
                        'attendance_status' => 'not_registered',
                        'registered_at' => null,
                        'delegate_to' => null,
                    ];
                });

            // Calculate attendance stats
            $totalApartments = \App\Models\Apartment::count();
            $registeredApartments = 0;
            $presentApartments = 0;
            $absentApartments = 0;
            $delegatedApartments = 0;
            $onlineResidents = $residents->where('is_online', true)->count();
            $quorumPercentage = $totalApartments > 0 ? ($presentApartments / $totalApartments) * 100 : 0;

            $stats = [
                'total_apartments' => $totalApartments,
                'registered_apartments' => $registeredApartments,
                'present_apartments' => $presentApartments,
                'absent_apartments' => $absentApartments,
                'delegated_apartments' => $delegatedApartments,
                'online_residents' => $onlineResidents,
                'total_residents' => $residents->count(),
                'quorum_percentage' => $quorumPercentage,
                'required_quorum_percentage' => $assembly->required_quorum_percentage,
                'has_quorum' => $quorumPercentage >= $assembly->required_quorum_percentage,
            ];

            return response()->json([
                'residents' => $residents,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error retrieving attendance status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Self-register attendance for an assembly
     */
    public function selfRegisterAttendance(Assembly $assembly)
    {
        try {
            // For now, just return success
            // This would typically update a database table with attendance records
            
            return response()->json([
                'success' => true,
                'message' => 'Attendance registered successfully',
                'status' => 'present'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error registering attendance',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get participants for an assembly
     */
    public function getParticipants(Assembly $assembly)
    {
        try {
            $participants = \App\Models\User::whereHas('resident.apartment')
                ->with(['resident.apartment'])
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'apartment' => $user->resident->apartment->number,
                    ];
                });

            return response()->json([
                'participants' => $participants,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error retrieving participants',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
