<?php

namespace App\Http\Controllers;

use App\Models\Assembly;
use App\Models\User;
use App\Models\VoteDelegate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class VoteDelegateController extends Controller
{
    public function index(Request $request, Assembly $assembly)
    {
        $query = $assembly->delegates()
            ->with(['delegatorApartment', 'delegateUser', 'authorizedByUser']);

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('search')) {
            $query->whereHas('delegateUser', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        $delegates = $query->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Assemblies/Delegates/Index', [
            'assembly' => $assembly,
            'delegates' => $delegates,
            'filters' => $request->only(['status', 'search']),
            'canManage' => Auth::user()->can('manage assemblies'),
        ]);
    }

    public function store(Request $request, Assembly $assembly)
    {
        $validated = $request->validate([
            'delegator_apartment_id' => 'required|exists:apartments,id',
            'delegate_user_id' => 'required|exists:users,id',
            'expires_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if delegate already exists
        $existingDelegate = VoteDelegate::forAssembly($assembly->id)
            ->forApartment($validated['delegator_apartment_id'])
            ->first();

        if ($existingDelegate) {
            return redirect()->back()
                ->withErrors(['delegator_apartment_id' => 'Ya existe una delegación para este apartamento.']);
        }

        // Verify user can create delegate for this apartment
        $userApartment = Auth::user()->resident?->apartment_id;
        $canDelegate = $userApartment === $validated['delegator_apartment_id'] || 
                      Auth::user()->can('manage assemblies');

        if (!$canDelegate) {
            return redirect()->back()
                ->with('error', 'No tiene permisos para crear una delegación para este apartamento.');
        }

        VoteDelegate::create([
            ...$validated,
            'assembly_id' => $assembly->id,
            'authorized_by_user_id' => Auth::id(),
            'status' => 'pending',
        ]);

        return redirect()->route('assemblies.delegates.index', $assembly)
            ->with('success', 'Delegación creada exitosamente.');
    }

    public function show(Assembly $assembly, VoteDelegate $delegate)
    {
        $delegate->load([
            'delegatorApartment',
            'delegateUser',
            'authorizedByUser'
        ]);

        return Inertia::render('Assemblies/Delegates/Show', [
            'assembly' => $assembly,
            'delegate' => $delegate,
            'canManage' => Auth::user()->can('manage assemblies'),
            'canEdit' => Auth::id() === $delegate->authorized_by_user_id || Auth::user()->can('manage assemblies'),
        ]);
    }

    public function update(Request $request, Assembly $assembly, VoteDelegate $delegate)
    {
        if (!in_array($delegate->status, ['pending', 'active'])) {
            return redirect()->back()
                ->with('error', 'No se puede modificar una delegación revocada o expirada.');
        }

        // Check permissions
        $canEdit = Auth::id() === $delegate->authorized_by_user_id || 
                  Auth::user()->can('manage assemblies');

        if (!$canEdit) {
            return redirect()->back()
                ->with('error', 'No tiene permisos para editar esta delegación.');
        }

        $validated = $request->validate([
            'expires_at' => 'nullable|date|after:now',
            'notes' => 'nullable|string|max:500',
        ]);

        $delegate->update($validated);

        return redirect()->route('assemblies.delegates.show', [$assembly, $delegate])
            ->with('success', 'Delegación actualizada exitosamente.');
    }

    public function destroy(Assembly $assembly, VoteDelegate $delegate)
    {
        // Check permissions
        $canDelete = Auth::id() === $delegate->authorized_by_user_id || 
                    Auth::user()->can('manage assemblies');

        if (!$canDelete) {
            return redirect()->back()
                ->with('error', 'No tiene permisos para eliminar esta delegación.');
        }

        $delegate->delete();

        return redirect()->route('assemblies.delegates.index', $assembly)
            ->with('success', 'Delegación eliminada exitosamente.');
    }

    public function approve(Assembly $assembly, VoteDelegate $delegate)
    {
        if (!Auth::user()->can('manage assemblies')) {
            return redirect()->back()
                ->with('error', 'No tiene permisos para aprobar delegaciones.');
        }

        if (!$delegate->approve()) {
            return redirect()->back()
                ->with('error', 'No se pudo aprobar la delegación.');
        }

        return redirect()->back()
            ->with('success', 'Delegación aprobada exitosamente.');
    }

    public function revoke(Assembly $assembly, VoteDelegate $delegate)
    {
        // Check permissions - either the authorizer or admin can revoke
        $canRevoke = Auth::id() === $delegate->authorized_by_user_id ||
                    Auth::user()->can('manage assemblies');

        if (!$canRevoke) {
            return redirect()->back()
                ->with('error', 'No tiene permisos para revocar esta delegación.');
        }

        if (!$delegate->revoke()) {
            return redirect()->back()
                ->with('error', 'No se pudo revocar la delegación.');
        }

        return redirect()->back()
            ->with('success', 'Delegación revocada exitosamente.');
    }

    /**
     * API: List delegates for an assembly
     */
    public function apiIndex(Request $request, Assembly $assembly)
    {
        try {
            $user = Auth::user();

            $query = $assembly->delegates()
                ->with(['delegatorApartment', 'delegateUser', 'authorizedByUser']);

            // For residents, only show their own delegations (as delegator or delegate)
            if (!$user->can('manage assemblies')) {
                $query->where(function ($q) use ($user) {
                    $q->where('delegate_user_id', $user->id)
                      ->orWhere('authorized_by_user_id', $user->id);
                });
            }

            if ($request->filled('status')) {
                $query->byStatus($request->status);
            }

            $delegates = $query->orderByDesc('created_at')
                ->paginate($request->get('per_page', 10));

            return response()->json([
                'success' => true,
                'data' => [
                    'delegates' => $delegates->items(),
                    'pagination' => [
                        'current_page' => $delegates->currentPage(),
                        'per_page' => $delegates->perPage(),
                        'total' => $delegates->total(),
                        'last_page' => $delegates->lastPage(),
                    ],
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving delegates',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Create delegation
     */
    public function apiStore(Request $request, Assembly $assembly)
    {
        try {
            $user = Auth::user();

            if (!$user->resident || !$user->resident->apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'User must have an assigned apartment to create delegations',
                ], 403);
            }

            $validated = $request->validate([
                'delegate_user_id' => 'required|exists:users,id',
                'expires_at' => 'nullable|date|after:now',
                'notes' => 'nullable|string|max:500',
            ]);

            $delegate = VoteDelegate::create([
                'assembly_id' => $assembly->id,
                'delegator_apartment_id' => $user->resident->apartment_id,
                'delegate_user_id' => $validated['delegate_user_id'],
                'authorized_by_user_id' => $user->id,
                'expires_at' => $validated['expires_at'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Delegación creada exitosamente',
                'data' => [
                    'delegate' => $delegate->load(['delegatorApartment', 'delegateUser'])
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating delegation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Delete delegation
     */
    public function apiDestroy(Assembly $assembly, VoteDelegate $delegate)
    {
        try {
            $user = Auth::user();

            // Check permissions - either the authorizer or admin can revoke
            $canRevoke = $user->id === $delegate->authorized_by_user_id ||
                        $user->can('manage assemblies');

            if (!$canRevoke) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para revocar esta delegación',
                ], 403);
            }

            if (!$delegate->revoke()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo revocar la delegación',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Delegación revocada exitosamente',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error revoking delegation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
