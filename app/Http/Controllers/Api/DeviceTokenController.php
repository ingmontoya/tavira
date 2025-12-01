<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Device Token Controller - Central Database
 *
 * Manages FCM device tokens in the central database to enable
 * cross-tenant push notifications for police/security personnel.
 */
class DeviceTokenController extends Controller
{
    /**
     * Register or update a device token for push notifications.
     *
     * This endpoint stores tokens in the central database to allow
     * proximity-based notifications across tenants.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:512',
            'platform' => 'required|in:ios,android,web',
            'device_name' => 'nullable|string|max:255',
            'tenant_id' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);

        $user = auth()->user();

        // Check if token already exists
        $existingToken = DeviceToken::where('token', $validated['token'])->first();

        if ($existingToken) {
            // If token exists for different user, transfer it
            if ($existingToken->user_id !== $user->id) {
                $updateData = [
                    'user_id' => $user->id,
                    'platform' => $validated['platform'],
                    'device_name' => $validated['device_name'] ?? $existingToken->device_name,
                    'tenant_id' => $validated['tenant_id'] ?? $existingToken->tenant_id,
                    'is_active' => true,
                    'last_used_at' => now(),
                ];

                // Update location if provided
                if (isset($validated['latitude']) && isset($validated['longitude'])) {
                    $updateData['last_latitude'] = $validated['latitude'];
                    $updateData['last_longitude'] = $validated['longitude'];
                    $updateData['location_updated_at'] = now();
                }

                $existingToken->update($updateData);

                Log::info('Device token transferred to new user', [
                    'token_id' => $existingToken->id,
                    'new_user_id' => $user->id,
                    'tenant_id' => $validated['tenant_id'] ?? null,
                ]);
            } else {
                // Update existing token
                $updateData = [
                    'platform' => $validated['platform'],
                    'device_name' => $validated['device_name'] ?? $existingToken->device_name,
                    'tenant_id' => $validated['tenant_id'] ?? $existingToken->tenant_id,
                    'is_active' => true,
                    'last_used_at' => now(),
                ];

                // Update location if provided
                if (isset($validated['latitude']) && isset($validated['longitude'])) {
                    $updateData['last_latitude'] = $validated['latitude'];
                    $updateData['last_longitude'] = $validated['longitude'];
                    $updateData['location_updated_at'] = now();
                }

                $existingToken->update($updateData);
            }

            return response()->json([
                'success' => true,
                'message' => 'Token de dispositivo actualizado',
                'token_id' => $existingToken->id,
            ]);
        }

        // Create new token
        $createData = [
            'user_id' => $user->id,
            'tenant_id' => $validated['tenant_id'] ?? null,
            'token' => $validated['token'],
            'platform' => $validated['platform'],
            'device_name' => $validated['device_name'] ?? null,
            'is_active' => true,
            'last_used_at' => now(),
        ];

        // Add location if provided
        if (isset($validated['latitude']) && isset($validated['longitude'])) {
            $createData['last_latitude'] = $validated['latitude'];
            $createData['last_longitude'] = $validated['longitude'];
            $createData['location_updated_at'] = now();
        }

        $deviceToken = DeviceToken::create($createData);

        Log::info('New device token registered', [
            'token_id' => $deviceToken->id,
            'user_id' => $user->id,
            'tenant_id' => $validated['tenant_id'] ?? null,
            'platform' => $validated['platform'],
            'has_location' => isset($validated['latitude']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token de dispositivo registrado',
            'token_id' => $deviceToken->id,
        ], 201);
    }

    /**
     * Get all device tokens for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $query = DeviceToken::where('user_id', auth()->id());

        // Optionally filter by tenant
        if ($request->has('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        $tokens = $query->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'tenant_id' => $token->tenant_id,
                    'platform' => $token->platform,
                    'platform_display' => $token->platform_display,
                    'device_name' => $token->device_name,
                    'is_active' => $token->is_active,
                    'has_location' => $token->hasLocation(),
                    'last_used_at' => $token->last_used_at?->toISOString(),
                    'location_updated_at' => $token->location_updated_at?->toISOString(),
                    'created_at' => $token->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'tokens' => $tokens,
        ]);
    }

    /**
     * Update device location for proximity-based notifications.
     *
     * This should be called periodically by the mobile app to keep
     * the device's location up to date for receiving nearby alerts.
     */
    public function updateLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:512',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $deviceToken = DeviceToken::where('token', $validated['token'])
            ->where('user_id', auth()->id())
            ->first();

        if (! $deviceToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token de dispositivo no encontrado',
            ], 404);
        }

        $deviceToken->updateLocation($validated['latitude'], $validated['longitude']);

        Log::debug('Device location updated', [
            'token_id' => $deviceToken->id,
            'latitude' => $validated['latitude'],
            'longitude' => $validated['longitude'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Ubicación actualizada',
        ]);
    }

    /**
     * Deactivate/delete a device token.
     */
    public function destroy(string $token): JsonResponse
    {
        // Find by token string or ID
        $deviceToken = DeviceToken::where('token', $token)
            ->orWhere('id', $token)
            ->first();

        if (! $deviceToken) {
            return response()->json([
                'success' => true,
                'message' => 'Token no encontrado o ya eliminado',
            ]);
        }

        // Only allow deleting own tokens (unless admin)
        if ($deviceToken->user_id !== auth()->id() &&
            ! auth()->user()->hasAnyRole(['superadmin', 'admin_conjunto'])) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado',
            ], 403);
        }

        $deviceToken->delete();

        Log::info('Device token deleted', [
            'token_id' => $deviceToken->id,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Token de dispositivo eliminado',
        ]);
    }

    /**
     * Deactivate all tokens for current user (logout from all devices).
     */
    public function deactivateAll(): JsonResponse
    {
        $count = DeviceToken::where('user_id', auth()->id())
            ->where('is_active', true)
            ->update(['is_active' => false]);

        Log::info('All device tokens deactivated', [
            'user_id' => auth()->id(),
            'count' => $count,
        ]);

        return response()->json([
            'success' => true,
            'message' => "Se desactivaron {$count} dispositivos",
            'deactivated_count' => $count,
        ]);
    }
}
