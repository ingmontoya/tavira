<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SecurityPersonnel;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Police Alerts Controller - Central API
 *
 * Provides endpoints for police officers (SecurityPersonnel) to:
 * - View panic alerts from all nearby residential complexes
 * - Respond to and resolve alerts
 * - Get statistics
 *
 * These endpoints work across tenants, querying all tenant databases.
 */
class PoliceAlertsController extends Controller
{
    /**
     * Get active panic alerts from all tenants.
     *
     * Optionally filter by officer's location and radius.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:1|max:100',
        ]);

        $officerLat = $request->input('latitude');
        $officerLng = $request->input('longitude');
        $radiusKm = $request->input('radius_km', 10);

        $allAlerts = $this->fetchAlertsFromAllTenants($officerLat, $officerLng, $radiusKm);

        return response()->json([
            'success' => true,
            'alerts' => $allAlerts,
            'meta' => [
                'total_alerts' => count($allAlerts),
                'tenants_searched' => Tenant::count(),
                'search_radius_km' => $radiusKm,
                'officer_location' => $officerLat && $officerLng ? [
                    'latitude' => (float) $officerLat,
                    'longitude' => (float) $officerLng,
                ] : null,
            ],
        ]);
    }

    /**
     * Get statistics for police dashboard.
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'radius_km' => 'nullable|numeric|min:1|max:100',
        ]);

        $officerLat = $request->input('latitude');
        $officerLng = $request->input('longitude');
        $radiusKm = $request->input('radius_km', 10);

        $user = $request->user();
        $userId = $user instanceof SecurityPersonnel ? $user->id : $user->id;

        // Get counts from all tenants
        $stats = $this->fetchStatsFromAllTenants($officerLat, $officerLng, $radiusKm, $userId);

        return response()->json([
            'success' => true,
            'stats' => $stats,
        ]);
    }

    /**
     * Respond to a panic alert (police officer accepts to attend).
     */
    public function respond(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|string',
            'alert_id' => 'required|integer',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $tenantId = $request->input('tenant_id');
        $alertId = $request->input('alert_id');
        $user = $request->user();

        try {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conjunto no encontrado',
                ], 404);
            }

            // Configure tenant connection
            $tenantDbName = 'tenant'.$tenant->id;
            $this->configureTenantConnection($tenantDbName);

            // Check if alert exists and is active
            $alert = DB::connection('temp_police')
                ->table('panic_alerts')
                ->where('id', $alertId)
                ->first();

            if (! $alert) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alerta no encontrada',
                ], 404);
            }

            if (! in_array($alert->status, ['triggered', 'confirmed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta alerta ya no está activa',
                ], 400);
            }

            if ($alert->responded_by) {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta alerta ya fue atendida por otro oficial',
                ], 400);
            }

            // Update alert with responder info
            // For SecurityPersonnel, store the UUID. For regular users, store the ID.
            $responderId = $user instanceof SecurityPersonnel ? $user->id : $user->id;
            $responderName = $user->name;

            DB::connection('temp_police')
                ->table('panic_alerts')
                ->where('id', $alertId)
                ->update([
                    'responded_by' => $responderId,
                    'responded_at' => now(),
                    'response_type' => 'accepted',
                    'status' => 'confirmed',
                    'responder_name' => $responderName, // Store name for cross-database reference
                    'responder_latitude' => $request->input('latitude'),
                    'responder_longitude' => $request->input('longitude'),
                    'updated_at' => now(),
                ]);

            // Get updated alert data
            $updatedAlert = DB::connection('temp_police')
                ->table('panic_alerts')
                ->join('users', 'panic_alerts.user_id', '=', 'users.id')
                ->leftJoin('apartments', 'panic_alerts.apartment_id', '=', 'apartments.id')
                ->where('panic_alerts.id', $alertId)
                ->select([
                    'panic_alerts.*',
                    'users.name as user_name',
                    'users.phone as user_phone',
                    'apartments.number as apartment_number',
                ])
                ->first();

            DB::purge('temp_police');

            Log::info('Police officer responded to panic alert', [
                'alert_id' => $alertId,
                'tenant_id' => $tenantId,
                'responder_id' => $responderId,
                'responder_name' => $responderName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Has aceptado atender esta alerta',
                'alert' => [
                    'id' => $updatedAlert->id,
                    'tenant_id' => $tenantId,
                    'tenant_name' => $tenant->id,
                    'user_name' => $updatedAlert->user_name,
                    'user_phone' => $updatedAlert->user_phone,
                    'apartment' => $updatedAlert->apartment_number ? "Apto {$updatedAlert->apartment_number}" : 'No especificado',
                    'latitude' => $updatedAlert->lat ? (float) $updatedAlert->lat : null,
                    'longitude' => $updatedAlert->lng ? (float) $updatedAlert->lng : null,
                    'status' => $updatedAlert->status,
                    'responded_at' => $updatedAlert->responded_at,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error responding to panic alert', [
                'alert_id' => $alertId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al responder a la alerta',
            ], 500);
        }
    }

    /**
     * Resolve a panic alert.
     */
    public function resolve(Request $request): JsonResponse
    {
        $request->validate([
            'tenant_id' => 'required|string',
            'alert_id' => 'required|integer',
            'notes' => 'nullable|string|max:1000',
        ]);

        $tenantId = $request->input('tenant_id');
        $alertId = $request->input('alert_id');
        $notes = $request->input('notes');
        $user = $request->user();

        try {
            $tenant = Tenant::find($tenantId);
            if (! $tenant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conjunto no encontrado',
                ], 404);
            }

            // Configure tenant connection
            $tenantDbName = 'tenant'.$tenant->id;
            $this->configureTenantConnection($tenantDbName);

            // Check if alert exists
            $alert = DB::connection('temp_police')
                ->table('panic_alerts')
                ->where('id', $alertId)
                ->first();

            if (! $alert) {
                return response()->json([
                    'success' => false,
                    'message' => 'Alerta no encontrada',
                ], 404);
            }

            if ($alert->status === 'resolved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Esta alerta ya fue resuelta',
                ], 400);
            }

            // Resolve the alert
            $resolverId = $user instanceof SecurityPersonnel ? $user->id : $user->id;
            $resolverName = $user->name;

            DB::connection('temp_police')
                ->table('panic_alerts')
                ->where('id', $alertId)
                ->update([
                    'status' => 'resolved',
                    'resolved_by' => $resolverId,
                    'resolved_at' => now(),
                    'resolver_name' => $resolverName,
                    'resolution_notes' => $notes,
                    'updated_at' => now(),
                ]);

            DB::purge('temp_police');

            Log::info('Police officer resolved panic alert', [
                'alert_id' => $alertId,
                'tenant_id' => $tenantId,
                'resolver_id' => $resolverId,
                'resolver_name' => $resolverName,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Alerta marcada como resuelta',
            ]);

        } catch (\Exception $e) {
            Log::error('Error resolving panic alert', [
                'alert_id' => $alertId,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al resolver la alerta',
            ], 500);
        }
    }

    /**
     * Fetch alerts from all tenant databases.
     */
    private function fetchAlertsFromAllTenants(?float $officerLat, ?float $officerLng, float $radiusKm): array
    {
        $tenants = Tenant::all();
        $allAlerts = [];

        foreach ($tenants as $tenant) {
            try {
                $tenantDbName = 'tenant'.$tenant->id;

                // Check if database exists
                $dbExists = DB::select('SELECT datname FROM pg_database WHERE datname = ?', [$tenantDbName]);
                if (empty($dbExists)) {
                    continue;
                }

                // Configure tenant connection
                $this->configureTenantConnection($tenantDbName);

                // Check if panic_alerts table exists
                $tableExists = DB::connection('temp_police')
                    ->select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'panic_alerts')");

                if (empty($tableExists) || ! $tableExists[0]->exists) {
                    DB::purge('temp_police');

                    continue;
                }

                // Get active panic alerts
                $alerts = DB::connection('temp_police')
                    ->table('panic_alerts')
                    ->join('users', 'panic_alerts.user_id', '=', 'users.id')
                    ->leftJoin('apartments', 'panic_alerts.apartment_id', '=', 'apartments.id')
                    ->whereIn('panic_alerts.status', ['triggered', 'confirmed'])
                    ->select([
                        'panic_alerts.id',
                        'panic_alerts.lat',
                        'panic_alerts.lng',
                        'panic_alerts.status',
                        'panic_alerts.created_at',
                        'panic_alerts.responded_by',
                        'panic_alerts.responded_at',
                        'panic_alerts.responder_name',
                        'users.name as user_name',
                        'users.phone as user_phone',
                        'apartments.number as apartment_number',
                    ])
                    ->orderBy('panic_alerts.created_at', 'desc')
                    ->get();

                foreach ($alerts as $alert) {
                    // Calculate distance if officer location provided
                    $distanceKm = null;
                    if ($officerLat && $officerLng && $alert->lat && $alert->lng) {
                        $distanceKm = $this->calculateDistance(
                            $officerLat,
                            $officerLng,
                            (float) $alert->lat,
                            (float) $alert->lng
                        );

                        // Skip if outside radius
                        if ($distanceKm > $radiusKm) {
                            continue;
                        }
                    }

                    // Get tenant address from tenant data
                    $tenantAddress = $tenant->data['address'] ?? null;

                    $allAlerts[] = [
                        'id' => (string) $alert->id,
                        'tenant_id' => $tenant->id,
                        'tenant_name' => $tenant->id,
                        'tenant_address' => $tenantAddress,
                        'user_name' => $alert->user_name,
                        'user_phone' => $alert->user_phone,
                        'apartment' => $alert->apartment_number ? "Apto {$alert->apartment_number}" : 'No especificado',
                        'latitude' => $alert->lat ? (float) $alert->lat : null,
                        'longitude' => $alert->lng ? (float) $alert->lng : null,
                        'status' => $alert->status,
                        'severity' => 'critical',
                        'created_at' => $alert->created_at,
                        'time_ago' => Carbon::parse($alert->created_at)->diffForHumans(),
                        'distance_km' => $distanceKm ? round($distanceKm, 2) : null,
                        'is_responded' => ! empty($alert->responded_by),
                        'responded_at' => $alert->responded_at,
                        'responder_name' => $alert->responder_name,
                    ];
                }

                DB::purge('temp_police');

            } catch (\Exception $e) {
                Log::warning("Error loading panic alerts for tenant {$tenant->id}: {$e->getMessage()}");
                DB::purge('temp_police');

                continue;
            }
        }

        // Sort by distance if available, otherwise by created_at
        usort($allAlerts, function ($a, $b) {
            if ($a['distance_km'] !== null && $b['distance_km'] !== null) {
                return $a['distance_km'] <=> $b['distance_km'];
            }

            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });

        return $allAlerts;
    }

    /**
     * Fetch statistics from all tenant databases.
     */
    private function fetchStatsFromAllTenants(?float $officerLat, ?float $officerLng, float $radiusKm, string $userId): array
    {
        $tenants = Tenant::all();
        $activeAlerts = 0;
        $resolvedToday = 0;
        $myResponses = 0;

        foreach ($tenants as $tenant) {
            try {
                $tenantDbName = 'tenant'.$tenant->id;

                // Check if database exists
                $dbExists = DB::select('SELECT datname FROM pg_database WHERE datname = ?', [$tenantDbName]);
                if (empty($dbExists)) {
                    continue;
                }

                // Configure tenant connection
                $this->configureTenantConnection($tenantDbName);

                // Check if panic_alerts table exists
                $tableExists = DB::connection('temp_police')
                    ->select("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_name = 'panic_alerts')");

                if (empty($tableExists) || ! $tableExists[0]->exists) {
                    DB::purge('temp_police');

                    continue;
                }

                // Count active alerts
                $activeAlerts += DB::connection('temp_police')
                    ->table('panic_alerts')
                    ->whereIn('status', ['triggered', 'confirmed'])
                    ->count();

                // Count resolved today
                $resolvedToday += DB::connection('temp_police')
                    ->table('panic_alerts')
                    ->where('status', 'resolved')
                    ->whereDate('resolved_at', today())
                    ->count();

                // Count my responses (where this officer responded)
                $myResponses += DB::connection('temp_police')
                    ->table('panic_alerts')
                    ->where('responded_by', $userId)
                    ->count();

                DB::purge('temp_police');

            } catch (\Exception $e) {
                Log::warning("Error loading stats for tenant {$tenant->id}: {$e->getMessage()}");
                DB::purge('temp_police');

                continue;
            }
        }

        return [
            'active_alerts' => $activeAlerts,
            'resolved_today' => $resolvedToday,
            'my_responses' => $myResponses,
            'tenants_monitored' => $tenants->count(),
        ];
    }

    /**
     * Configure a temporary database connection for a tenant.
     */
    private function configureTenantConnection(string $databaseName): void
    {
        config([
            'database.connections.temp_police' => [
                'driver' => 'pgsql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '5433'),
                'database' => $databaseName,
                'username' => env('DB_USERNAME', 'mauricio'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8',
                'prefix' => '',
                'prefix_indexes' => true,
                'schema' => 'public',
                'sslmode' => 'prefer',
            ],
        ]);
    }

    /**
     * Calculate distance between two points using Haversine formula.
     */
    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371; // km

        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($lngDelta / 2) * sin($lngDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
