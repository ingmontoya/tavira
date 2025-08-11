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
}
