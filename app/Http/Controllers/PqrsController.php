<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePqrsRequest;
use App\Http\Requests\UpdatePqrsRequest;
use App\Models\Apartment;
use App\Models\Pqrs;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PqrsController extends Controller
{
    /**
     * Display admin listing of all PQRS
     */
    public function index(Request $request): Response
    {
        $query = Pqrs::with(['user', 'apartment', 'assignedTo'])
            ->latest();

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->byStatus($request->status);
        }

        // Filter by type
        if ($request->has('type') && $request->type !== 'all') {
            $query->byType($request->type);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%")
                    ->orWhere('submitter_name', 'like', "%{$search}%")
                    ->orWhere('submitter_email', 'like', "%{$search}%");
            });
        }

        $pqrs = $query->paginate(15)->withQueryString();

        // Get statistics
        $stats = [
            'total' => Pqrs::count(),
            'pendiente' => Pqrs::byStatus('pendiente')->count(),
            'en_proceso' => Pqrs::byStatus('en_proceso')->count(),
            'resuelta' => Pqrs::byStatus('resuelta')->count(),
        ];

        return Inertia::render('PQRS/Index', [
            'pqrs' => $pqrs,
            'stats' => $stats,
            'filters' => $request->only(['status', 'type', 'search']),
            'users' => User::select('id', 'name', 'email')->get(),
        ]);
    }

    /**
     * Display public form for submitting PQRS
     */
    public function publicCreate(): Response
    {
        $apartments = Apartment::with('apartmentType')
            ->orderBy('number')
            ->get()
            ->map(fn ($apt) => [
                'id' => $apt->id,
                'number' => $apt->number,
                'type' => $apt->apartmentType->name ?? '',
            ]);

        return Inertia::render('PQRS/PublicCreate', [
            'apartments' => $apartments,
        ]);
    }

    /**
     * Store a newly created PQRS (public endpoint)
     */
    public function store(StorePqrsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // If user is authenticated, use their ID
        if (auth()->check()) {
            $data['user_id'] = auth()->id();
        }

        $pqrs = Pqrs::create($data);

        return redirect()
            ->route('pqrs.public.success', ['ticket' => $pqrs->ticket_number])
            ->with('success', 'Su PQRS ha sido enviada exitosamente. Número de ticket: '.$pqrs->ticket_number);
    }

    /**
     * Display success page after public submission
     */
    public function publicSuccess(string $ticket): Response
    {
        $pqrs = Pqrs::where('ticket_number', $ticket)->firstOrFail();

        return Inertia::render('PQRS/PublicSuccess', [
            'pqrs' => $pqrs,
        ]);
    }

    /**
     * Display the specified PQRS
     */
    public function show(Pqrs $pqrs): Response
    {
        $pqrs->load(['user', 'apartment.apartmentType', 'assignedTo']);

        return Inertia::render('PQRS/Show', [
            'pqrs' => $pqrs,
            'users' => User::select('id', 'name', 'email')->get(),
        ]);
    }

    /**
     * Update the specified PQRS (admin only)
     */
    public function update(UpdatePqrsRequest $request, Pqrs $pqrs): RedirectResponse
    {
        $data = $request->validated();

        // Update timestamps based on status changes
        if (isset($data['status'])) {
            if ($data['status'] === 'resuelta' && ! $pqrs->resolved_at) {
                $data['resolved_at'] = now();
            }
        }

        if (isset($data['admin_response']) && ! $pqrs->responded_at) {
            $data['responded_at'] = now();
        }

        $pqrs->update($data);

        return redirect()
            ->back()
            ->with('success', 'PQRS actualizada exitosamente');
    }

    /**
     * Remove the specified PQRS
     */
    public function destroy(Pqrs $pqrs): RedirectResponse
    {
        $this->authorize('manage_pqrs');

        $pqrs->delete();

        return redirect()
            ->route('pqrs.index')
            ->with('success', 'PQRS eliminada exitosamente');
    }

    /**
     * Public tracking page - check PQRS status by ticket number
     */
    public function track(Request $request): Response
    {
        $pqrs = null;

        if ($request->has('ticket')) {
            $pqrs = Pqrs::where('ticket_number', $request->ticket)
                ->with(['apartment'])
                ->first();
        }

        return Inertia::render('PQRS/Track', [
            'pqrs' => $pqrs,
            'ticket' => $request->ticket,
        ]);
    }
}
