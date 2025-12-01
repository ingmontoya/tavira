<?php

namespace App\Http\Controllers\Api;

use App\Events\PanicAlertTriggered;
use App\Events\PanicAlertUpdated;
use App\Http\Controllers\Controller;
use App\Models\PanicAlert;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PanicAlertController extends Controller
{
    public function __construct(
        private FcmService $fcmService
    ) {}

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

        // Load relationships for notifications
        $panicAlert->load(['user', 'apartment']);

        // Broadcast the event (WebSocket)
        event(new PanicAlertTriggered($panicAlert));

        // Send push notifications to police/security (FCM)
        try {
            $pushResult = $this->fcmService->sendPanicAlertToPolice($panicAlert);
            Log::info('Push notifications sent for panic alert', [
                'alert_id' => $panicAlert->id,
                'sent_count' => $pushResult['sent_count'] ?? 0,
                'failed_count' => $pushResult['failed_count'] ?? 0,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send push notifications for panic alert', [
                'alert_id' => $panicAlert->id,
                'error' => $e->getMessage(),
            ]);
            // Don't fail the request if push fails - WebSocket is the primary channel
        }

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

        // Broadcast the status update
        event(new PanicAlertUpdated($panicAlert));

        // Clear cache
        \Cache::forget('panic_alerts:active:'.tenant('id'));

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
        // Check if user has permission (admin, security, porteria, or admin_conjunto roles)
        if (! auth()->user()->hasAnyRole(['superadmin', 'admin_conjunto', 'seguridad', 'consejo', 'porteria'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para resolver alertas',
            ], 403);
        }

        if (! $panicAlert->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta alerta no está activa',
            ], 400);
        }

        $panicAlert->update(['status' => 'resolved']);

        // Broadcast the status update
        event(new PanicAlertUpdated($panicAlert));

        // Clear cache
        \Cache::forget('panic_alerts:active:'.tenant('id'));

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
        if (! auth()->user()->hasAnyRole(['superadmin', 'admin_conjunto', 'seguridad', 'consejo', 'porteria'])) {
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
     * Get active alerts for global banner.
     * CACHED: This endpoint is now cached aggressively since real-time updates
     * are handled via WebSockets. Only used as fallback/bootstrap.
     */
    public function active()
    {
        // Check if user has permission to view alerts
        if (! auth()->user()->hasAnyRole(['superadmin', 'admin_conjunto', 'seguridad', 'consejo', 'porteria'])) {
            return response()->json([
                'success' => true,
                'alerts' => [], // Return empty array instead of error for better UX
            ]);
        }

        // Cache for 30 seconds to reduce DB load
        // Real-time updates are handled via WebSockets (Laravel Reverb)
        $cacheKey = 'panic_alerts:active:'.tenant('id');

        $alerts = \Cache::remember($cacheKey, 30, function () {
            return PanicAlert::with(['user', 'apartment'])
                ->active()
                ->orderBy('created_at', 'desc')
                ->limit(10) // Limit to 10 most recent
                ->get();
        });

        $transformedAlerts = $alerts->map(function ($alert) {
            return [
                'id' => $alert->id,
                'type' => 'panic',
                'message' => 'Alerta de pánico activada',
                'user_name' => $alert->user_display_name,
                'apartment' => $alert->apartment_display_name,
                'location' => $alert->location_string,
                'status' => $alert->status,
                'severity' => 'critical', // Panic alerts are always critical
                'created_at' => $alert->created_at->toISOString(),
            ];
        });

        return response()->json([
            'success' => true,
            'alerts' => $transformedAlerts,
        ]);
    }

    /**
     * Acknowledge a panic alert.
     */
    public function acknowledge(PanicAlert $panicAlert)
    {
        // Check if user has permission
        if (! auth()->user()->hasAnyRole(['superadmin', 'admin_conjunto', 'seguridad', 'consejo', 'porteria'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para reconocer alertas',
            ], 403);
        }

        if ($panicAlert->status !== 'triggered') {
            return response()->json([
                'success' => false,
                'message' => 'Esta alerta no puede ser reconocida',
            ], 400);
        }

        $panicAlert->update(['status' => 'confirmed']);

        // Broadcast the status update
        event(new PanicAlertUpdated($panicAlert));

        // Clear cache
        \Cache::forget('panic_alerts:active:'.tenant('id'));

        return response()->json([
            'success' => true,
            'message' => 'Alerta reconocida',
            'alert' => [
                'id' => $panicAlert->id,
                'status' => $panicAlert->status,
                'updated_at' => $panicAlert->updated_at->toISOString(),
            ],
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

    /**
     * Respond to a panic alert (police officer accepts to attend).
     */
    public function respond(PanicAlert $panicAlert)
    {
        // Check if user has permission (police, security roles)
        if (! auth()->user()->hasAnyRole(['superadmin', 'admin_conjunto', 'seguridad', 'policia', 'porteria'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para responder a alertas',
            ], 403);
        }

        // Check if alert is still active and available
        if (! $panicAlert->isAvailableForResponse()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta alerta ya fue atendida por otro oficial',
                'current_responder' => $panicAlert->responder_display_name,
            ], 400);
        }

        // Mark alert as responded
        $panicAlert->update([
            'responded_by' => auth()->id(),
            'responded_at' => now(),
            'response_type' => 'accepted',
            'status' => 'confirmed', // Move to confirmed status when someone responds
        ]);

        // Broadcast the update
        event(new PanicAlertUpdated($panicAlert->fresh(['user', 'apartment', 'responder'])));

        // Clear cache
        \Cache::forget('panic_alerts:active:'.tenant('id'));

        Log::info('Panic alert responded to', [
            'alert_id' => $panicAlert->id,
            'responder_id' => auth()->id(),
            'responder_name' => auth()->user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Has aceptado atender esta alerta',
            'alert' => [
                'id' => $panicAlert->id,
                'status' => $panicAlert->status,
                'responded_by' => auth()->user()->name,
                'responded_at' => $panicAlert->responded_at->toISOString(),
                'user_name' => $panicAlert->user_display_name,
                'apartment' => $panicAlert->apartment_display_name,
                'latitude' => $panicAlert->lat,
                'longitude' => $panicAlert->lng,
            ],
        ]);
    }

    /**
     * Reject a panic alert (police officer declines to attend).
     */
    public function reject(PanicAlert $panicAlert)
    {
        // Check if user has permission
        if (! auth()->user()->hasAnyRole(['superadmin', 'admin_conjunto', 'seguridad', 'policia', 'porteria'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para rechazar alertas',
            ], 403);
        }

        // Check if alert is still active
        if (! $panicAlert->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Esta alerta ya no está activa',
            ], 400);
        }

        // Log the rejection but don't assign the alert to this user
        // The alert remains available for other officers
        Log::info('Panic alert rejected', [
            'alert_id' => $panicAlert->id,
            'rejected_by' => auth()->id(),
            'rejected_by_name' => auth()->user()->name,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Has rechazado esta alerta. Otro oficial puede atenderla.',
            'alert' => [
                'id' => $panicAlert->id,
                'status' => $panicAlert->status,
            ],
        ]);
    }

    /**
     * Get a single panic alert details (for map view after responding).
     */
    public function show(PanicAlert $panicAlert)
    {
        // Check if user has permission
        if (! auth()->user()->hasAnyRole(['superadmin', 'admin_conjunto', 'seguridad', 'policia', 'porteria'])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para ver esta alerta',
            ], 403);
        }

        $panicAlert->load(['user', 'apartment', 'responder', 'resolver']);

        return response()->json([
            'success' => true,
            'alert' => [
                'id' => $panicAlert->id,
                'user_name' => $panicAlert->user_display_name,
                'apartment' => $panicAlert->apartment_display_name,
                'latitude' => $panicAlert->lat ? (float) $panicAlert->lat : null,
                'longitude' => $panicAlert->lng ? (float) $panicAlert->lng : null,
                'location' => $panicAlert->location_string,
                'status' => $panicAlert->status,
                'status_badge' => $panicAlert->status_badge,
                'severity' => 'critical',
                'created_at' => $panicAlert->created_at->toISOString(),
                'time_elapsed' => $panicAlert->created_at->diffForHumans(),
                'responded_by' => $panicAlert->responder_display_name,
                'responded_at' => $panicAlert->responded_at?->toISOString(),
                'resolved_by' => $panicAlert->resolver_display_name,
                'resolved_at' => $panicAlert->resolved_at?->toISOString(),
                'resolution_notes' => $panicAlert->resolution_notes,
                'is_active' => $panicAlert->isActive(),
            ],
        ]);
    }

    /**
     * Get active panic alerts for admin dashboard (cross-tenant).
     */
    public function getActiveAlertsForAdmin()
    {
        // This endpoint works from the central domain, so we need to check all tenants
        $tenants = \App\Models\Tenant::all();
        $activeAlerts = [];

        foreach ($tenants as $tenant) {
            try {
                $tenantDbName = 'tenant'.$tenant->id;

                // Check if database exists
                $dbExists = \DB::select('SELECT datname FROM pg_database WHERE datname = ?', [$tenantDbName]);
                if (empty($dbExists)) {
                    continue;
                }

                // Configure tenant connection dynamically
                config([
                    'database.connections.temp_alerts' => [
                        'driver' => 'pgsql',
                        'host' => env('DB_HOST', '127.0.0.1'),
                        'port' => env('DB_PORT', '5433'),
                        'database' => $tenantDbName,
                        'username' => env('DB_USERNAME', 'mauricio'),
                        'password' => env('DB_PASSWORD', ''),
                        'charset' => 'utf8',
                        'prefix' => '',
                        'prefix_indexes' => true,
                        'schema' => 'public',
                        'sslmode' => 'prefer',
                    ],
                ]);

                // Get active panic alerts from this tenant
                $alerts = \DB::connection('temp_alerts')
                    ->table('panic_alerts')
                    ->join('users', 'panic_alerts.user_id', '=', 'users.id')
                    ->leftJoin('apartments', 'panic_alerts.apartment_id', '=', 'apartments.id')
                    ->where('panic_alerts.status', 'triggered')
                    ->select([
                        'panic_alerts.id',
                        'panic_alerts.lat',
                        'panic_alerts.lng',
                        'panic_alerts.status',
                        'panic_alerts.created_at',
                        'users.name as user_name',
                        'apartments.number as apartment_number',
                    ])
                    ->get();

                foreach ($alerts as $alert) {
                    $activeAlerts[] = [
                        'id' => $alert->id,
                        'alert_id' => $alert->id,
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->id, // You might have a name field
                        'user' => [
                            'name' => $alert->user_name,
                        ],
                        'apartment' => [
                            'address' => $alert->apartment_number ? "Apartamento {$alert->apartment_number}" : 'No especificado',
                        ],
                        'location' => [
                            'lat' => $alert->lat,
                            'lng' => $alert->lng,
                        ],
                        'status' => $alert->status,
                        'timestamp' => $alert->created_at,
                        'time_ago' => \Carbon\Carbon::parse($alert->created_at)->diffForHumans(),
                    ];
                }

                // Clean up connection
                \DB::purge('temp_alerts');

            } catch (\Exception $e) {
                \Log::warning("Error loading panic alerts for tenant {$tenant->id}: {$e->getMessage()}");

                continue;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $activeAlerts,
        ]);
    }
}
