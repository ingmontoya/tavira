<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReservationRequest;
use App\Models\ReservableAsset;
use App\Models\Reservation;
use Illuminate\Http\Request;

class ReservationController extends Controller
{
    /**
     * Display a listing of the user's reservations.
     */
    public function index(Request $request)
    {
        $query = Reservation::with(['reservableAsset', 'apartment'])
            ->where('user_id', auth()->id());

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('upcoming')) {
            if ($request->boolean('upcoming')) {
                $query->where('start_time', '>', now());
            } else {
                $query->where('end_time', '<', now());
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('start_time', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('start_time', '<=', $request->date_to);
        }

        $reservations = $query->orderBy('start_time', 'desc')
            ->paginate($request->get('per_page', 15));

        // Transform reservations for API response
        $reservations->getCollection()->transform(function ($reservation) {
            return $this->transformReservation($reservation);
        });

        return response()->json([
            'success' => true,
            'reservations' => $reservations,
        ]);
    }

    /**
     * Store a newly created reservation.
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
        $reservation->load(['reservableAsset', 'apartment']);

        $message = $asset->requires_approval
            ? 'Reservation created successfully. Pending approval.'
            : 'Reservation created and approved successfully.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'reservation' => $this->transformReservation($reservation),
        ], 201);
    }

    /**
     * Display the specified reservation.
     */
    public function show(Reservation $reservation)
    {
        // Check authorization - users can only see their own reservations
        if ($reservation->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $reservation->load(['reservableAsset', 'apartment', 'approvedBy', 'cancelledBy']);

        return response()->json([
            'success' => true,
            'reservation' => $this->transformReservation($reservation, true),
        ]);
    }

    /**
     * Update the specified reservation.
     */
    public function update(StoreReservationRequest $request, Reservation $reservation)
    {
        // Check authorization
        if ($reservation->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (! $reservation->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'This reservation cannot be modified',
            ], 400);
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
        $reservation->load(['reservableAsset', 'apartment']);

        return response()->json([
            'success' => true,
            'message' => 'Reservation updated successfully',
            'reservation' => $this->transformReservation($reservation),
        ]);
    }

    /**
     * Cancel the specified reservation.
     */
    public function destroy(Reservation $reservation)
    {
        // Check authorization
        if ($reservation->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        if (! $reservation->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'This reservation cannot be cancelled',
            ], 400);
        }

        $reservation->cancel(auth()->id());

        return response()->json([
            'success' => true,
            'message' => 'Reservation cancelled successfully',
        ]);
    }

    /**
     * Get reservation statistics for the current user.
     */
    public function stats()
    {
        $userId = auth()->id();

        $totalReservations = Reservation::where('user_id', $userId)->count();
        $upcomingReservations = Reservation::where('user_id', $userId)
            ->where('status', 'approved')
            ->where('start_time', '>', now())
            ->count();
        $pendingApproval = Reservation::where('user_id', $userId)
            ->where('status', 'pending')
            ->count();
        $completedReservations = Reservation::where('user_id', $userId)
            ->where('status', 'completed')
            ->count();

        return response()->json([
            'success' => true,
            'stats' => [
                'total_reservations' => $totalReservations,
                'upcoming_reservations' => $upcomingReservations,
                'pending_approval' => $pendingApproval,
                'completed_reservations' => $completedReservations,
            ],
        ]);
    }

    /**
     * Get upcoming reservations for the current user.
     */
    public function upcoming()
    {
        $reservations = Reservation::with(['reservableAsset', 'apartment'])
            ->where('user_id', auth()->id())
            ->whereIn('status', ['approved', 'pending'])
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->take(10)
            ->get();

        $reservations = $reservations->map(function ($reservation) {
            return $this->transformReservation($reservation);
        });

        return response()->json([
            'success' => true,
            'upcoming_reservations' => $reservations,
        ]);
    }

    /**
     * Transform a reservation for API response.
     */
    private function transformReservation(Reservation $reservation, bool $detailed = false)
    {
        $data = [
            'id' => $reservation->id,
            'asset_name' => $reservation->reservableAsset->name,
            'asset_id' => $reservation->reservable_asset_id,
            'apartment_number' => $reservation->apartment?->number,
            'start_time' => $reservation->start_time->toISOString(),
            'end_time' => $reservation->end_time->toISOString(),
            'status' => $reservation->status,
            'status_label' => $reservation->status_label,
            'status_color' => $reservation->status_color,
            'cost' => (float) $reservation->cost,
            'payment_required' => $reservation->payment_required,
            'payment_status' => $reservation->payment_status,
            'notes' => $reservation->notes,
            'duration' => $reservation->getDurationFormatted(),
            'can_be_cancelled' => $reservation->canBeCancelled(),
            'is_in_progress' => $reservation->isInProgress(),
            'is_upcoming' => $reservation->start_time > now(),
            'created_at' => $reservation->created_at->toISOString(),
        ];

        if ($detailed) {
            $data = array_merge($data, [
                'asset_details' => [
                    'id' => $reservation->reservableAsset->id,
                    'name' => $reservation->reservableAsset->name,
                    'description' => $reservation->reservableAsset->description,
                    'type' => $reservation->reservableAsset->type,
                    'image_url' => $reservation->reservableAsset->image_path ?
                        asset('storage/'.$reservation->reservableAsset->image_path) : null,
                ],
                'admin_notes' => $reservation->admin_notes,
                'approved_at' => $reservation->approved_at?->toISOString(),
                'approved_by_name' => $reservation->approvedBy?->name,
                'cancelled_at' => $reservation->cancelled_at?->toISOString(),
                'cancelled_by_name' => $reservation->cancelledBy?->name,
                'metadata' => $reservation->metadata,
            ]);
        }

        return $data;
    }
}
