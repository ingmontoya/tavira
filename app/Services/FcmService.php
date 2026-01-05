<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\PanicAlert;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Firebase Cloud Messaging Service
 *
 * This service handles sending push notifications via FCM.
 * It operates on the central database to enable cross-tenant notifications.
 * Police officers can receive alerts from nearby residential complexes.
 */
class FcmService
{
    private string $projectId;

    private string $serviceAccountPath;

    private ?string $accessToken = null;

    private ?int $tokenExpiresAt = null;

    public function __construct()
    {
        $this->projectId = config('services.fcm.project_id') ?? '';
        $this->serviceAccountPath = config('services.fcm.service_account_path') ?? '';
    }

    /**
     * Send a panic alert notification to police/security officers.
     *
     * This method sends notifications to:
     * 1. All security personnel in the same tenant
     * 2. Security personnel from nearby tenants (within alert radius)
     *
     * @param  PanicAlert  $alert  The panic alert to notify about
     * @param  string|null  $tenantId  The tenant where the alert originated (defaults to current tenant)
     */
    public function sendPanicAlertToPolice(PanicAlert $alert, ?string $tenantId = null): array
    {
        $tenantId = $tenantId ?? tenant('id');
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            Log::warning('Cannot send panic alert: tenant not found', ['tenant_id' => $tenantId]);

            return [
                'success' => false,
                'message' => 'Tenant no encontrado',
                'sent_count' => 0,
                'failed_count' => 0,
            ];
        }

        // Get device tokens for this alert
        // Uses alert coordinates if available, otherwise uses tenant location
        $alertLat = $alert->lat;
        $alertLng = $alert->lng;

        $tokens = DeviceToken::getSecurityTokensForAlert($tenantId, $alertLat, $alertLng);

        if ($tokens->isEmpty()) {
            Log::warning('No active device tokens found for panic alert', [
                'tenant_id' => $tenantId,
                'alert_id' => $alert->id,
            ]);

            return [
                'success' => false,
                'message' => 'No hay dispositivos registrados para recibir alertas',
                'sent_count' => 0,
                'failed_count' => 0,
            ];
        }

        // Log token breakdown for debugging
        $tenantTokenCount = $tokens->where('tenant_id', $tenantId)->count();
        $securityPersonnelTokenCount = $tokens->where('user_type', 'security_personnel')->count();
        $otherTokenCount = $tokens->count() - $tenantTokenCount - $securityPersonnelTokenCount;

        Log::info('Sending panic alert to devices', [
            'alert_id' => $alert->id,
            'tenant_id' => $tenantId,
            'total_device_count' => $tokens->count(),
            'tenant_tokens' => $tenantTokenCount,
            'security_personnel_tokens' => $securityPersonnelTokenCount,
            'nearby_tokens' => $otherTokenCount,
            'has_location' => $alertLat && $alertLng,
        ]);

        $payload = $this->buildPanicAlertPayload($alert, $tenant);
        $results = $this->sendToMultipleDevices($tokens, $payload);

        return $results;
    }

    /**
     * Send notification to a specific user's devices.
     */
    public function sendToUser(User $user, array $notification, array $data = [], ?string $tenantId = null): array
    {
        $query = DeviceToken::where('user_id', $user->id)->active();

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $tokens = $query->get();

        if ($tokens->isEmpty()) {
            return [
                'success' => false,
                'message' => 'Usuario no tiene dispositivos registrados',
                'sent_count' => 0,
            ];
        }

        $payload = $this->buildPayload($notification, $data);

        return $this->sendToMultipleDevices($tokens, $payload);
    }

    /**
     * Send to multiple devices.
     */
    public function sendToMultipleDevices(Collection $deviceTokens, array $payload): array
    {
        $results = [
            'success' => true,
            'sent_count' => 0,
            'failed_count' => 0,
            'failed_tokens' => [],
        ];

        foreach ($deviceTokens as $deviceToken) {
            $result = $this->sendToDevice($deviceToken->token, $payload, $deviceToken->platform);

            if ($result['success']) {
                $results['sent_count']++;
                $deviceToken->markAsUsed();
            } else {
                $results['failed_count']++;
                $results['failed_tokens'][] = [
                    'token_id' => $deviceToken->id,
                    'error' => $result['error'] ?? 'Unknown error',
                ];

                // Deactivate token if it's invalid
                if ($this->isInvalidTokenError($result['error'] ?? '')) {
                    $deviceToken->deactivate();
                    Log::info("Deactivated invalid device token: {$deviceToken->id}");
                }
            }
        }

        $results['success'] = $results['sent_count'] > 0;

        return $results;
    }

    /**
     * Send notification to a single device.
     */
    public function sendToDevice(string $token, array $payload, string $platform = 'android'): array
    {
        try {
            $accessToken = $this->getAccessToken();

            if (! $accessToken) {
                return [
                    'success' => false,
                    'error' => 'Could not obtain FCM access token',
                ];
            }

            $message = [
                'message' => array_merge(
                    ['token' => $token],
                    $payload,
                    $this->getPlatformConfig($platform)
                ),
            ];

            $response = Http::withToken($accessToken)
                ->timeout(30)
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                    $message
                );

            if ($response->successful()) {
                Log::info('FCM notification sent successfully', [
                    'token' => substr($token, 0, 20).'...',
                    'platform' => $platform,
                ]);

                return ['success' => true, 'response' => $response->json()];
            }

            $error = $response->json('error.message') ?? $response->body();
            Log::error('FCM notification failed', [
                'token' => substr($token, 0, 20).'...',
                'error' => $error,
                'status' => $response->status(),
            ]);

            return ['success' => false, 'error' => $error];

        } catch (\Exception $e) {
            Log::error('FCM notification exception', [
                'token' => substr($token, 0, 20).'...',
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Build the payload for a panic alert notification.
     */
    private function buildPanicAlertPayload(PanicAlert $alert, Tenant $tenant): array
    {
        $userName = $alert->user?->name ?? 'Usuario desconocido';
        $apartment = $alert->apartment?->full_address ?? 'Ubicación desconocida';
        $timeAgo = $alert->created_at->diffForHumans();
        $tenantName = $tenant->id; // Could be replaced with a friendly name

        return [
            'notification' => [
                'title' => '🚨 ALERTA DE PÁNICO',
                'body' => "{$userName} - {$apartment}\n{$tenantName} • {$timeAgo}",
            ],
            'data' => [
                'type' => 'panic_alert',
                'alertId' => $alert->id,
                'alert_id' => $alert->id,
                'tenantId' => $tenant->id,
                'tenant_id' => $tenant->id,
                'userName' => $userName,
                'user_name' => $userName,
                'apartment' => $apartment,
                'latitude' => (string) ($alert->lat ?? ''),
                'longitude' => (string) ($alert->lng ?? ''),
                'status' => $alert->status,
                'severity' => 'critical',
                'timestamp' => $alert->created_at->toISOString(),
                'click_action' => 'OPEN_PANIC_ALERT',
            ],
        ];
    }

    /**
     * Build a generic notification payload.
     */
    private function buildPayload(array $notification, array $data = []): array
    {
        return [
            'notification' => $notification,
            'data' => array_map('strval', $data), // FCM data must be strings
        ];
    }

    /**
     * Get platform-specific configuration.
     */
    private function getPlatformConfig(string $platform): array
    {
        if ($platform === 'android') {
            return [
                'android' => [
                    'priority' => 'high',
                    'ttl' => '300s', // 5 minutes TTL for urgent alerts
                    'notification' => [
                        'channel_id' => 'alertas_panico',
                        'notification_priority' => 'PRIORITY_MAX',
                        'visibility' => 'PUBLIC',
                        'default_sound' => true,
                        'default_vibrate_timings' => true,
                        'notification_count' => 1,
                        'click_action' => 'OPEN_PANIC_ALERT',
                    ],
                    'direct_boot_ok' => true, // Deliver even before device unlock
                ],
            ];
        }

        if ($platform === 'ios') {
            return [
                'apns' => [
                    'headers' => [
                        'apns-priority' => '10', // Highest priority
                        'apns-push-type' => 'alert',
                    ],
                    'payload' => [
                        'aps' => [
                            'alert' => [
                                'title' => '🚨 ALERTA DE PÁNICO',
                            ],
                            'sound' => [
                                'name' => 'default',
                                'critical' => 1, // Critical alert (bypasses DND)
                                'volume' => 1.0,
                            ],
                            'badge' => 1,
                            'interruption-level' => 'critical', // iOS 15+ critical interruption
                            'relevance-score' => 1.0,
                            'category' => 'PANIC_ALERT',
                            'mutable-content' => 1,
                            'content-available' => 1,
                        ],
                    ],
                ],
            ];
        }

        // Web platform
        return [
            'webpush' => [
                'headers' => [
                    'Urgency' => 'high',
                    'TTL' => '300',
                ],
                'notification' => [
                    'icon' => '/icon-192x192.png',
                    'badge' => '/icon-192x192.png',
                    'requireInteraction' => true,
                    'renotify' => true,
                    'tag' => 'panic-alert',
                    'vibrate' => [500, 200, 500, 200, 500],
                ],
            ],
        ];
    }

    /**
     * Get OAuth2 access token for FCM API v1.
     */
    private function getAccessToken(): ?string
    {
        // Return cached token if still valid
        if ($this->accessToken && $this->tokenExpiresAt && time() < $this->tokenExpiresAt - 60) {
            return $this->accessToken;
        }

        try {
            if (! file_exists($this->serviceAccountPath)) {
                Log::error('FCM service account file not found', [
                    'path' => $this->serviceAccountPath,
                ]);

                return null;
            }

            $serviceAccount = json_decode(file_get_contents($this->serviceAccountPath), true);

            if (! $serviceAccount) {
                Log::error('Invalid FCM service account JSON');

                return null;
            }

            // Create JWT
            $now = time();
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = base64_encode(json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $signatureInput = $header.'.'.$payload;
            $signature = '';
            openssl_sign($signatureInput, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = $signatureInput.'.'.base64_encode($signature);

            // Exchange JWT for access token
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->successful()) {
                $this->accessToken = $response->json('access_token');
                $this->tokenExpiresAt = time() + $response->json('expires_in', 3600);

                return $this->accessToken;
            }

            Log::error('Failed to get FCM access token', [
                'error' => $response->body(),
            ]);

            return null;

        } catch (\Exception $e) {
            Log::error('Exception getting FCM access token', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Check if the error indicates an invalid token.
     */
    private function isInvalidTokenError(string $error): bool
    {
        $invalidTokenErrors = [
            'UNREGISTERED',
            'INVALID_ARGUMENT',
            'NOT_FOUND',
            'registration-token-not-registered',
            'invalid-registration-token',
        ];

        foreach ($invalidTokenErrors as $invalidError) {
            if (stripos($error, $invalidError) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if FCM is properly configured.
     */
    public function isConfigured(): bool
    {
        return ! empty($this->projectId) && file_exists($this->serviceAccountPath);
    }
}
