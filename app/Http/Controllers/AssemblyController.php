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

        return Inertia::render('Assemblies/Show', [
            'assembly' => $assembly,
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
}
