<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConjuntoConfig;
use App\Models\ReservableAsset;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReservableAssetController extends Controller
{
    /**
     * Display a listing of available assets for reservations.
     */
    public function index(Request $request)
    {
        $conjuntoConfig = ConjuntoConfig::first();

        if (! $conjuntoConfig) {
            return response()->json([
                'success' => false,
                'message' => 'No conjunto configuration found',
                'assets' => [],
            ]);
        }

        $query = ReservableAsset::where('conjunto_config_id', $conjuntoConfig->id)
            ->active()
            ->withCount(['activeReservations']);

        // Apply filters
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('description', 'like', '%'.$request->search.'%');
            });
        }

        $assets = $query->orderBy('name')->get();

        // Transform assets for API response
        $assets = $assets->map(function ($asset) {
            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'description' => $asset->description,
                'type' => $asset->type,
                'image_url' => $asset->image_path ? asset('storage/'.$asset->image_path) : null,
                'reservation_duration_minutes' => $asset->reservation_duration_minutes,
                'reservation_cost' => (float) $asset->reservation_cost,
                'advance_booking_days' => $asset->advance_booking_days,
                'max_reservations_per_user' => $asset->max_reservations_per_user,
                'requires_approval' => $asset->requires_approval,
                'availability_rules' => $asset->availability_rules,
                'active_reservations_count' => $asset->active_reservations_count,
                'can_user_reserve' => $asset->canUserReserve(auth()->id()),
            ];
        });

        return response()->json([
            'success' => true,
            'assets' => $assets,
        ]);
    }

    /**
     * Display the specified asset with detailed information.
     */
    public function show(ReservableAsset $reservableAsset)
    {
        if (! $reservableAsset->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not available',
            ], 404);
        }

        $reservableAsset->loadCount(['reservations', 'activeReservations']);

        // Get upcoming reservations for this asset
        $upcomingReservations = $reservableAsset->reservations()
            ->with(['user:id,name', 'apartment:id,number'])
            ->where('status', 'approved')
            ->where('start_time', '>', now())
            ->orderBy('start_time')
            ->take(10)
            ->get()
            ->map(function ($reservation) {
                return [
                    'id' => $reservation->id,
                    'start_time' => $reservation->start_time->toISOString(),
                    'end_time' => $reservation->end_time->toISOString(),
                    'user_name' => $reservation->user->name,
                    'apartment_number' => $reservation->apartment?->number,
                ];
            });

        return response()->json([
            'success' => true,
            'asset' => [
                'id' => $reservableAsset->id,
                'name' => $reservableAsset->name,
                'description' => $reservableAsset->description,
                'type' => $reservableAsset->type,
                'image_url' => $reservableAsset->image_path ? asset('storage/'.$reservableAsset->image_path) : null,
                'reservation_duration_minutes' => $reservableAsset->reservation_duration_minutes,
                'reservation_cost' => (float) $reservableAsset->reservation_cost,
                'advance_booking_days' => $reservableAsset->advance_booking_days,
                'max_reservations_per_user' => $reservableAsset->max_reservations_per_user,
                'requires_approval' => $reservableAsset->requires_approval,
                'availability_rules' => $reservableAsset->availability_rules,
                'total_reservations' => $reservableAsset->reservations_count,
                'active_reservations_count' => $reservableAsset->active_reservations_count,
                'can_user_reserve' => $reservableAsset->canUserReserve(auth()->id()),
                'upcoming_reservations' => $upcomingReservations,
            ],
        ]);
    }

    /**
     * Get availability slots for a specific asset and date.
     */
    public function availability(Request $request, ReservableAsset $reservableAsset)
    {
        $request->validate([
            'date' => 'required|date|after_or_equal:today',
        ]);

        if (! $reservableAsset->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Asset not available',
            ], 404);
        }

        $date = Carbon::parse($request->date);

        // Check if date is within booking window
        if ($date->gt($reservableAsset->getMaxAdvanceBookingDate())) {
            return response()->json([
                'success' => false,
                'message' => "Reservations can only be made up to {$reservableAsset->advance_booking_days} days in advance",
                'slots' => [],
            ]);
        }

        // Generate time slots based on asset duration
        $slots = [];
        $start = $date->copy()->setTime(6, 0); // Start at 6:00 AM
        $end = $date->copy()->setTime(23, 59); // End at 11:59 PM
        $durationMinutes = $reservableAsset->reservation_duration_minutes;

        while ($start->lt($end)) {
            $slotEnd = $start->copy()->addMinutes($durationMinutes);

            // Don't add slots that would go past midnight
            if ($slotEnd->greaterThan($date->copy()->setTime(23, 59))) {
                break;
            }

            $isAvailable = $reservableAsset->isAvailableAt($start, $slotEnd);

            $slots[] = [
                'start_time' => $start->format('H:i'),
                'end_time' => $slotEnd->format('H:i'),
                'start_datetime' => $start->toISOString(),
                'end_datetime' => $slotEnd->toISOString(),
                'available' => $isAvailable,
            ];

            $start->addMinutes($durationMinutes);
        }

        return response()->json([
            'success' => true,
            'date' => $date->toDateString(),
            'asset' => [
                'id' => $reservableAsset->id,
                'name' => $reservableAsset->name,
                'duration_minutes' => $reservableAsset->reservation_duration_minutes,
                'cost' => (float) $reservableAsset->reservation_cost,
            ],
            'slots' => $slots,
        ]);
    }

    /**
     * Get asset types for filtering.
     */
    public function types()
    {
        $types = [
            'common_area' => 'Common Area',
            'amenity' => 'Amenity',
            'facility' => 'Facility',
            'sports' => 'Sports',
            'recreation' => 'Recreation',
            'meeting_room' => 'Meeting Room',
            'event_space' => 'Event Space',
        ];

        return response()->json([
            'success' => true,
            'types' => $types,
        ]);
    }
}
