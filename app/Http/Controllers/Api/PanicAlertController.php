<?php

namespace App\Http\Controllers\Api;

use App\Events\PanicAlertTriggered;
use App\Http\Controllers\Controller;
use App\Models\PanicAlert;
use Illuminate\Http\Request;

class PanicAlertController extends Controller
{
    /**
     * Store a newly created panic alert.
     */
    public function store(Request $request)
    {
        $request->validate([
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
        ]);

        // Get user's apartment from their resident record
        $apartment = auth()->user()->apartment;

        $panicAlert = PanicAlert::create([
            'user_id' => auth()->id(),
            'apartment_id' => $apartment?->id,
            'lat' => $request->lat,
            'lng' => $request->lng,
            'status' => 'triggered',
        ]);

        // Broadcast the event
        event(new PanicAlertTriggered($panicAlert));

        return response()->json([
            'success' => true,
            'message' => 'Alerta de pánico activada. Los servicios de seguridad han sido notificados.',
            'alert' => [
                'id' => $panicAlert->id,
                'status' => $panicAlert->status,
                'created_at' => $panicAlert->created_at->toISOString(),
                'countdown_expires_at' => $panicAlert->created_at->addSeconds(10)->toISOString(),
            ],
        ], 201);
    }

    /**
     * Cancel a panic alert within the 10-second window.
     */
    public function cancel(PanicAlert $panicAlert)
    {
        // Check authorization
        if ($panicAlert->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        // Check if alert can still be cancelled (within 10 seconds and triggered status)
        if ($panicAlert->status !== 'triggered') {
            return response()->json([
                'success' => false,
                'message' => 'Esta alerta no puede ser cancelada',
            ], 400);
        }

        $secondsSinceCreated = $panicAlert->created_at->diffInSeconds(now());
        if ($secondsSinceCreated > 10) {
            return response()->json([
                'success' => false,
                'message' => 'El tiempo para cancelar la alerta ha expirado',
            ], 400);
        }

        $panicAlert->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Alerta de pánico cancelada',
            'alert' => [
                'id' => $panicAlert->id,
                'status' => $panicAlert->status,
                'updated_at' => $panicAlert->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Resolve a panic alert (for security personnel).
     */
    public function resolve(PanicAlert $panicAlert)
    {
        // Check if user has permission (admin, security, or admin_conjunto roles)
        if (!auth()->user()->hasAnyRole(['superadmin', 'admin_conjunto', 'seguridad', 'consejo'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para resolver alertas',
            ], 403);
        }

        if (!$panicAlert->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta alerta no está activa',
            ], 400);
        }

        $panicAlert->update(['status' => 'resolved']);

        return response()->json([
            'success' => true,
            'message' => 'Alerta de pánico marcada como resuelta',
            'alert' => [
                'id' => $panicAlert->id,
                'status' => $panicAlert->status,
                'updated_at' => $panicAlert->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Get active panic alerts (for security dashboard).
     */
    public function index()
    {
        // Check if user has permission to view alerts
        if (!auth()->user()->hasAnyRole(['superadmin', 'admin_conjunto', 'seguridad', 'consejo'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver las alertas',
            ], 403);
        }

        $alerts = PanicAlert::with(['user', 'apartment'])
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();

        $transformedAlerts = $alerts->map(function ($alert) {
            return [
                'id' => $alert->id,
                'user_name' => $alert->user_display_name,
                'apartment' => $alert->apartment_display_name,
                'location' => $alert->location_string,
                'status' => $alert->status,
                'status_badge' => $alert->status_badge,
                'created_at' => $alert->created_at->toISOString(),
                'time_elapsed' => $alert->created_at->diffForHumans(),
                'is_active' => $alert->isActive(),
            ];
        });

        return response()->json([
            'success' => true,
            'alerts' => $transformedAlerts,
        ]);
    }

    /**
     * Get user's own panic alert history.
     */
    public function history()
    {
        $alerts = PanicAlert::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $alerts->getCollection()->transform(function ($alert) {
            return [
                'id' => $alert->id,
                'status' => $alert->status,
                'status_badge' => $alert->status_badge,
                'location' => $alert->location_string,
                'created_at' => $alert->created_at->toISOString(),
                'time_ago' => $alert->created_at->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'alerts' => $alerts,
        ]);
    }
}