<?php

namespace App\Http\Controllers;

use App\Models\Apartment;
use App\Models\Visit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class VisitController extends Controller
{
    public function index(Request $request): Response
    {
        $user = Auth::user();
        $query = Visit::with(['apartment', 'creator']);

        if ($user->hasRole('residente') || $user->hasRole('propietario')) {
            $apartment = $user->residents->first()?->apartment;
            if ($apartment) {
                $query->where('apartment_id', $apartment->id);
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('visitor_name', 'like', "%{$search}%")
                    ->orWhere('visitor_document_number', 'like', "%{$search}%")
                    ->orWhere('qr_code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $visits = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Visits/Index', [
            'visits' => $visits,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(): Response
    {
        $user = Auth::user();
        $apartments = collect();

        if ($user->hasRole('admin_conjunto') || $user->hasRole('superadmin')) {
            $apartments = Apartment::orderBy('tower')->orderBy('number')->get();
        } elseif ($user->hasRole('residente') || $user->hasRole('propietario')) {
            $apartment = $user->residents->first()?->apartment;
            if ($apartment) {
                $apartments = collect([$apartment]);
            }
        }

        return Inertia::render('Visits/Create', [
            'apartments' => $apartments->map(function ($apartment) {
                return [
                    'id' => $apartment->id,
                    'number' => $apartment->number,
                    'tower' => $apartment->tower,
                    'floor' => $apartment->floor,
                ];
            })->values(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'apartment_id' => ['required', 'exists:apartments,id'],
            'visitor_name' => ['required', 'string', 'max:255'],
            'visitor_document_type' => ['required', 'in:CC,CE,PP,TI'],
            'visitor_document_number' => ['required', 'string', 'max:20'],
            'visitor_phone' => ['nullable', 'string', 'max:20'],
            'visit_reason' => ['nullable', 'string', 'max:500'],
            'valid_from' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    $validFrom = \Carbon\Carbon::parse($value);
                    $now = \Carbon\Carbon::now()->subMinutes(5); // Allow 5 minutes tolerance

                    if ($validFrom->lt($now)) {
                        $fail('La fecha de inicio debe ser posterior o igual al momento actual.');
                    }
                },
            ],
            'valid_until' => ['required', 'date', 'after:valid_from'],
        ]);

        if ($user->hasRole('residente') || $user->hasRole('propietario')) {
            $apartment = $user->residents->first()?->apartment;
            if (! $apartment || $apartment->id != $request->apartment_id) {
                abort(403, 'No tienes permiso para crear visitas para este apartamento.');
            }
        }

        Visit::create([
            'apartment_id' => $request->apartment_id,
            'created_by' => $user->id,
            'visitor_name' => $request->visitor_name,
            'visitor_document_type' => $request->visitor_document_type,
            'visitor_document_number' => $request->visitor_document_number,
            'visitor_phone' => $request->visitor_phone,
            'visit_reason' => $request->visit_reason,
            'valid_from' => $request->valid_from,
            'valid_until' => $request->valid_until,
            'status' => 'pending',
        ]);

        return redirect()->route('visits.index')
            ->with('success', 'Visita creada exitosamente.');
    }

    public function show(Visit $visit): Response
    {
        $user = Auth::user();

        if ($user->hasRole('residente') || $user->hasRole('propietario')) {
            $apartment = $user->residents->first()?->apartment;
            if (! $apartment || $visit->apartment_id !== $apartment->id) {
                abort(403);
            }
        }

        $visit->load(['apartment', 'creator', 'authorizer']);

        return Inertia::render('Visits/Show', [
            'visit' => $visit,
        ]);
    }

    public function destroy(Visit $visit): RedirectResponse
    {
        $user = Auth::user();

        if ($user->hasRole('residente') || $user->hasRole('propietario')) {
            $apartment = $user->residents->first()?->apartment;
            if (! $apartment || $visit->apartment_id !== $apartment->id) {
                abort(403);
            }
        }

        if ($visit->status === 'used') {
            return redirect()->back()
                ->with('error', 'No se puede cancelar una visita que ya fue utilizada.');
        }

        $visit->update(['status' => 'cancelled']);

        return redirect()->route('visits.index')
            ->with('success', 'Visita cancelada exitosamente.');
    }

    // API methods for mobile app

    /**
     * API: Get visits for authenticated resident.
     */
    public function apiResidentIndex(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get user's apartment
            $apartment = $user->apartment;
            if (!$apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró apartamento asociado al usuario.',
                ], 404);
            }

            // Build query for user's apartment visits
            $query = Visit::with(['apartment', 'creator'])
                ->where('apartment_id', $apartment->id);

            // Optional filtering by status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Optional search
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('visitor_name', 'like', "%{$search}%")
                        ->orWhere('visitor_document_number', 'like', "%{$search}%");
                });
            }

            // Order by most recent first
            $query->orderBy('created_at', 'desc');

            // Paginate results (default 15 per page for mobile)
            $perPage = $request->get('per_page', 15);
            $visits = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $visits->items(),
                'meta' => [
                    'current_page' => $visits->currentPage(),
                    'last_page' => $visits->lastPage(),
                    'per_page' => $visits->perPage(),
                    'total' => $visits->total(),
                    'from' => $visits->firstItem(),
                    'to' => $visits->lastItem(),
                ],
                'apartment' => [
                    'number' => $apartment->number,
                    'tower' => $apartment->tower,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las visitas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Create a new visit.
     */
    public function apiStore(Request $request)
    {
        try {
            $user = $request->user();
            
            // Get user's apartment
            $apartment = $user->apartment;
            if (!$apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró apartamento asociado al usuario.',
                ], 404);
            }

            $validated = $request->validate([
                'visitor_name' => ['required', 'string', 'max:255'],
                'visitor_document_type' => ['required', 'in:CC,CE,PP,TI'],
                'visitor_document_number' => ['required', 'string', 'max:20'],
                'visitor_phone' => ['nullable', 'string', 'max:20'],
                'visit_reason' => ['nullable', 'string', 'max:500'],
                'valid_from' => [
                    'required',
                    'date',
                    function ($attribute, $value, $fail) {
                        $validFrom = \Carbon\Carbon::parse($value);
                        $now = \Carbon\Carbon::now()->subMinutes(5);
                        if ($validFrom->lt($now)) {
                            $fail('La fecha de inicio debe ser posterior o igual al momento actual.');
                        }
                    },
                ],
                'valid_until' => ['required', 'date', 'after:valid_from'],
            ]);

            $visit = Visit::create([
                'apartment_id' => $apartment->id,
                'created_by' => $user->id,
                'visitor_name' => $validated['visitor_name'],
                'visitor_document_type' => $validated['visitor_document_type'],
                'visitor_document_number' => $validated['visitor_document_number'],
                'visitor_phone' => $validated['visitor_phone'] ?? null,
                'visit_reason' => $validated['visit_reason'] ?? null,
                'valid_from' => $validated['valid_from'],
                'valid_until' => $validated['valid_until'],
                'status' => 'pending',
            ]);

            $visit->load(['apartment', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Visita creada exitosamente.',
                'data' => $visit,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la visita: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get detailed visit information.
     */
    public function apiShow(Visit $visit)
    {
        try {
            $user = request()->user();
            
            // Get user's apartment
            $apartment = $user->apartment;
            if (!$apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró apartamento asociado al usuario.',
                ], 404);
            }

            // Check if visit belongs to user's apartment
            if ($visit->apartment_id !== $apartment->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver esta visita.',
                ], 403);
            }

            $visit->load(['apartment', 'creator', 'authorizer']);

            return response()->json([
                'success' => true,
                'data' => $visit,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la visita: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Update a visit.
     */
    public function apiUpdate(Request $request, Visit $visit)
    {
        try {
            $user = $request->user();
            
            // Get user's apartment
            $apartment = $user->apartment;
            if (!$apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró apartamento asociado al usuario.',
                ], 404);
            }

            // Check if visit belongs to user's apartment
            if ($visit->apartment_id !== $apartment->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para editar esta visita.',
                ], 403);
            }

            // Only allow updates if visit is still pending
            if ($visit->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo se pueden editar visitas en estado pendiente.',
                ], 400);
            }

            $validated = $request->validate([
                'visitor_name' => ['sometimes', 'string', 'max:255'],
                'visitor_document_type' => ['sometimes', 'in:CC,CE,PP,TI'],
                'visitor_document_number' => ['sometimes', 'string', 'max:20'],
                'visitor_phone' => ['nullable', 'string', 'max:20'],
                'visit_reason' => ['nullable', 'string', 'max:500'],
                'valid_from' => [
                    'sometimes',
                    'date',
                    function ($attribute, $value, $fail) {
                        $validFrom = \Carbon\Carbon::parse($value);
                        $now = \Carbon\Carbon::now()->subMinutes(5);
                        if ($validFrom->lt($now)) {
                            $fail('La fecha de inicio debe ser posterior o igual al momento actual.');
                        }
                    },
                ],
                'valid_until' => ['sometimes', 'date', 'after:valid_from'],
            ]);

            $visit->update($validated);
            $visit->load(['apartment', 'creator']);

            return response()->json([
                'success' => true,
                'message' => 'Visita actualizada exitosamente.',
                'data' => $visit,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la visita: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Cancel/delete a visit.
     */
    public function apiDestroy(Visit $visit)
    {
        try {
            $user = request()->user();
            
            // Get user's apartment
            $apartment = $user->apartment;
            if (!$apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró apartamento asociado al usuario.',
                ], 404);
            }

            // Check if visit belongs to user's apartment
            if ($visit->apartment_id !== $apartment->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para cancelar esta visita.',
                ], 403);
            }

            if ($visit->status === 'used') {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede cancelar una visita que ya fue utilizada.',
                ], 400);
            }

            $visit->update(['status' => 'cancelled']);

            return response()->json([
                'success' => true,
                'message' => 'Visita cancelada exitosamente.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cancelar la visita: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Get QR code for a visit.
     */
    public function apiQrCode(Visit $visit)
    {
        try {
            $user = request()->user();
            
            // Get user's apartment
            $apartment = $user->apartment;
            if (!$apartment) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró apartamento asociado al usuario.',
                ], 404);
            }

            // Check if visit belongs to user's apartment
            if ($visit->apartment_id !== $apartment->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permiso para ver el código QR de esta visita.',
                ], 403);
            }

            // Check if visit is active
            if (!in_array($visit->status, ['pending', 'active'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código QR solo está disponible para visitas pendientes o activas.',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'qr_code' => $visit->qr_code,
                    'visit_id' => $visit->id,
                    'visitor_name' => $visit->visitor_name,
                    'valid_from' => $visit->valid_from?->toISOString(),
                    'valid_until' => $visit->valid_until?->toISOString(),
                    'status' => $visit->status,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el código QR: ' . $e->getMessage(),
            ], 500);
        }
    }
}
