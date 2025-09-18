<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReservationRequest;
use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\ReservableAsset;
use App\Models\Reservation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Reservation::with(['reservableAsset', 'user', 'apartment', 'approvedBy', 'cancelledBy']);

        // Filter by user role - residents only see their own reservations
        if (! auth()->user()->hasAnyRole(['admin_conjunto', 'superadmin'])) {
            $query->where('user_id', auth()->id());
        }

        // Apply filters
        if ($request->filled('search')) {
            $query->whereHas('reservableAsset', function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%');
            })
                ->orWhereHas('user', function ($q) use ($request) {
                    $q->where('name', 'like', '%'.$request->search.'%');
                });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('asset_id')) {
            $query->where('reservable_asset_id', $request->asset_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $reservations = $query->orderBy('start_time', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get available assets for filter
        $conjuntoConfig = ConjuntoConfig::first();
        $assets = $conjuntoConfig ?
            ReservableAsset::where('conjunto_config_id', $conjuntoConfig->id)
                ->active()
                ->orderBy('name')
                ->get(['id', 'name']) :
            collect([]);

        $statuses = [
            'pending' => 'Pendiente',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'cancelled' => 'Cancelada',
            'completed' => 'Completada',
        ];

        return Inertia::render('Reservations/Index', [
            'reservations' => $reservations,
            'assets' => $assets,
            'statuses' => $statuses,
            'filters' => $request->only(['search', 'status', 'asset_id', 'date_from', 'date_to']),
            'isAdmin' => auth()->user()->hasAnyRole(['admin_conjunto', 'superadmin']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $conjuntoConfig = ConjuntoConfig::first();

        if (! $conjuntoConfig) {
            return redirect()->route('conjunto-config.create')
                ->with('error', 'Debe configurar el conjunto antes de hacer reservas.');
        }

        $assets = ReservableAsset::where('conjunto_config_id', $conjuntoConfig->id)
            ->active()
            ->orderBy('name')
            ->get();

        // Pre-select asset if specified
        $selectedAsset = null;
        if ($request->filled('asset_id')) {
            $selectedAsset = $assets->find($request->asset_id);
        }

        // Get user's apartment if they are a resident
        $userApartment = null;
        if (auth()->user()->resident) {
            $userApartment = auth()->user()->resident->apartment;
        }

        // If admin, get all apartments for selection
        $apartments = auth()->user()->hasAnyRole(['admin_conjunto', 'superadmin']) ?
            Apartment::where('conjunto_config_id', $conjuntoConfig->id)
                ->orderBy('tower')
                ->orderBy('floor')
                ->orderBy('position_on_floor')
                ->get(['id', 'number', 'tower', 'floor']) :
            collect([]);

        return Inertia::render('Reservations/Create', [
            'assets' => $assets,
            'selectedAsset' => $selectedAsset,
            'userApartment' => $userApartment,
            'apartments' => $apartments,
            'isAdmin' => auth()->user()->hasAnyRole(['admin_conjunto', 'superadmin']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReservationRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // Get asset to calculate cost
        $asset = ReservableAsset::findOrFail($data['reservable_asset_id']);
        $data['cost'] = $asset->reservation_cost;
        $data['payment_required'] = $asset->reservation_cost > 0;

        // Set initial status based on asset approval requirements
        $data['status'] = $asset->requires_approval ? 'pending' : 'approved';

        if (! $asset->requires_approval) {
            $data['approved_at'] = now();
            $data['approved_by'] = auth()->id();
        }

        // Set apartment from user's resident record if not provided
        if (! isset($data['apartment_id']) && auth()->user()->resident) {
            $data['apartment_id'] = auth()->user()->resident->apartment_id;
        }

        $reservation = Reservation::create($data);

        $message = $asset->requires_approval
            ? 'Reserva creada exitosamente. Está pendiente de aprobación.'
            : 'Reserva creada y aprobada exitosamente.';

        return redirect()->route('reservations.index')
            ->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation)
    {
        // Check authorization - users can only see their own reservations
        if (! auth()->user()->hasAnyRole(['admin_conjunto', 'superadmin']) &&
            $reservation->user_id !== auth()->id()) {
            abort(403);
        }

        $reservation->load([
            'reservableAsset',
            'user',
            'apartment',
            'approvedBy',
            'cancelledBy',
        ]);

        return Inertia::render('Reservations/Show', [
            'reservation' => $reservation,
            'isAdmin' => auth()->user()->hasAnyRole(['admin_conjunto', 'superadmin']),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reservation $reservation)
    {
        // Check authorization - users can only edit their own pending reservations
        if (! auth()->user()->hasAnyRole(['admin_conjunto', 'superadmin']) &&
            $reservation->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $reservation->canBeCancelled()) {
            return back()->with('error', 'Esta reserva no puede ser modificada.');
        }

        $conjuntoConfig = ConjuntoConfig::first();
        $assets = ReservableAsset::where('conjunto_config_id', $conjuntoConfig->id)
            ->active()
            ->orderBy('name')
            ->get();

        return Inertia::render('Reservations/Edit', [
            'reservation' => $reservation,
            'assets' => $assets,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreReservationRequest $request, Reservation $reservation)
    {
        // Check authorization
        if (! auth()->user()->hasAnyRole(['admin_conjunto', 'superadmin']) &&
            $reservation->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $reservation->canBeCancelled()) {
            return back()->with('error', 'Esta reserva no puede ser modificada.');
        }

        $data = $request->validated();

        // Get asset to calculate new cost
        $asset = ReservableAsset::findOrFail($data['reservable_asset_id']);
        $data['cost'] = $asset->reservation_cost;
        $data['payment_required'] = $asset->reservation_cost > 0;

        // Reset status if asset changed and new asset requires approval
        if ($reservation->reservable_asset_id !== $data['reservable_asset_id'] && $asset->requires_approval) {
            $data['status'] = 'pending';
            $data['approved_at'] = null;
            $data['approved_by'] = null;
        }

        $reservation->update($data);

        return redirect()->route('reservations.index')
            ->with('success', 'Reserva actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation)
    {
        // Check authorization
        if (! auth()->user()->hasAnyRole(['admin_conjunto', 'superadmin']) &&
            $reservation->user_id !== auth()->id()) {
            abort(403);
        }

        if (! $reservation->canBeCancelled()) {
            return back()->with('error', 'Esta reserva no puede ser cancelada.');
        }

        $reservation->cancel(auth()->id());

        return redirect()->route('reservations.index')
            ->with('success', 'Reserva cancelada exitosamente.');
    }

    /**
     * Approve a reservation (admin only).
     */
    public function approve(Reservation $reservation)
    {
        if (! auth()->user()->hasAnyRole(['admin_conjunto', 'superadmin'])) {
            abort(403);
        }

        if (! $reservation->canBeApproved()) {
            return back()->with('error', 'Esta reserva no puede ser aprobada.');
        }

        $reservation->approve(auth()->id());

        return back()->with('success', 'Reserva aprobada exitosamente.');
    }

    /**
     * Reject a reservation (admin only).
     */
    public function reject(Reservation $reservation)
    {
        if (! auth()->user()->hasAnyRole(['admin_conjunto', 'superadmin'])) {
            abort(403);
        }

        if (! $reservation->canBeApproved()) {
            return back()->with('error', 'Esta reserva no puede ser rechazada.');
        }

        $reservation->reject(auth()->id());

        return back()->with('success', 'Reserva rechazada.');
    }

    /**
     * Get availability for a specific asset and date range.
     */
    public function availability(Request $request)
    {
        $request->validate([
            'asset_id' => 'required|exists:reservable_assets,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $asset = ReservableAsset::findOrFail($request->asset_id);
        $date = Carbon::parse($request->date);

        // Check if date is within booking window
        if ($date->gt($asset->getMaxAdvanceBookingDate())) {
            return response()->json([
                'available' => false,
                'message' => "Solo se pueden hacer reservas hasta {$asset->advance_booking_days} días de anticipación.",
            ]);
        }

        // Generate time slots based on asset duration
        $slots = [];
        $start = $date->copy()->setTime(6, 0); // Start at 6:00 AM
        $end = $date->copy()->setTime(23, 59); // End at 11:59 PM
        $durationMinutes = $asset->reservation_duration_minutes;

        while ($start->lt($end)) {
            $slotEnd = $start->copy()->addMinutes($durationMinutes);

            $isAvailable = $asset->isAvailableAt($start, $slotEnd);

            $slots[] = [
                'start' => $start->format('H:i'),
                'end' => $slotEnd->format('H:i'),
                'start_datetime' => $start->toISOString(),
                'end_datetime' => $slotEnd->toISOString(),
                'available' => $isAvailable,
            ];

            $start->addMinutes($durationMinutes);
        }

        return response()->json([
            'slots' => $slots,
            'asset' => $asset->only(['name', 'reservation_duration_minutes', 'reservation_cost']),
        ]);
    }
}
