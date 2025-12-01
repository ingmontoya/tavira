<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DeviceToken Model - Central Database
 *
 * Stores FCM device tokens centrally to enable cross-tenant notifications.
 * This allows police officers to receive panic alerts from nearby
 * residential complexes, not just their own.
 */
class DeviceToken extends Model
{
    use HasFactory, HasUuids;

    /**
     * Use the central database connection.
     */
    protected $connection = 'central';

    protected $fillable = [
        'user_id',
        'tenant_id',
        'token',
        'platform',
        'device_name',
        'is_active',
        'last_used_at',
        'last_latitude',
        'last_longitude',
        'location_updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'location_updated_at' => 'datetime',
        'last_latitude' => 'decimal:7',
        'last_longitude' => 'decimal:7',
    ];

    /**
     * Get the user that owns this device token.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the tenant this token belongs to.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to get only active tokens.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by platform.
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to get tokens for a specific tenant.
     */
    public function scopeForTenant($query, string $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope to get tokens for users with security/police roles.
     * This queries the tenant database for role information.
     */
    public function scopeForSecurityRoles($query)
    {
        // Get user IDs that have security roles
        // Note: This requires checking each tenant's database for roles
        return $query->whereIn('user_id', function ($subQuery) {
            $subQuery->select('model_id')
                ->from('model_has_roles')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->whereIn('roles.name', ['policia', 'seguridad', 'admin_conjunto', 'superadmin'])
                ->where('model_type', 'App\\Models\\User');
        });
    }

    /**
     * Scope to get tokens within a radius of given coordinates.
     *
     * Uses the Haversine formula to calculate distance.
     *
     * @param  float  $latitude  Center latitude
     * @param  float  $longitude  Center longitude
     * @param  float  $radiusKm  Radius in kilometers
     */
    public function scopeWithinRadius($query, float $latitude, float $longitude, float $radiusKm)
    {
        // Haversine formula for distance calculation
        $haversine = '(
            6371 * acos(
                cos(radians(?)) *
                cos(radians(last_latitude)) *
                cos(radians(last_longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(last_latitude))
            )
        )';

        return $query
            ->whereNotNull('last_latitude')
            ->whereNotNull('last_longitude')
            ->whereRaw("{$haversine} <= ?", [$latitude, $longitude, $latitude, $radiusKm]);
    }

    /**
     * Scope to get tokens near a tenant's location.
     */
    public function scopeNearTenant($query, Tenant $tenant, ?float $radiusKm = null)
    {
        if (! $tenant->latitude || ! $tenant->longitude) {
            // If tenant has no location, only return tokens from same tenant
            return $query->where('tenant_id', $tenant->id);
        }

        $radius = $radiusKm ?? $tenant->alert_radius_km ?? 5.0;

        return $query->withinRadius($tenant->latitude, $tenant->longitude, $radius);
    }

    /**
     * Get tokens for police/security users near a specific location.
     *
     * This is the main method for sending proximity-based alerts.
     */
    public static function getSecurityTokensNearLocation(
        float $latitude,
        float $longitude,
        float $radiusKm = 5.0,
        ?string $excludeTenantId = null
    ) {
        $query = static::active()
            ->withinRadius($latitude, $longitude, $radiusKm);

        if ($excludeTenantId) {
            $query->where('tenant_id', '!=', $excludeTenantId);
        }

        return $query->get();
    }

    /**
     * Get all tokens for security users in a tenant and nearby tenants.
     */
    public static function getSecurityTokensForAlert(
        string $tenantId,
        ?float $alertLatitude = null,
        ?float $alertLongitude = null
    ) {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            return collect();
        }

        // Start with tokens from the same tenant
        $query = static::active()->where('tenant_id', $tenantId);

        // If we have alert coordinates and tenant has a location, also get nearby tokens
        if ($alertLatitude && $alertLongitude && $tenant->latitude && $tenant->longitude) {
            $radius = $tenant->alert_radius_km ?? 5.0;

            // Get tokens from nearby locations (including other tenants)
            $nearbyQuery = static::active()
                ->withinRadius($alertLatitude, $alertLongitude, $radius)
                ->where('tenant_id', '!=', $tenantId);

            // Combine both queries
            return $query->get()->merge($nearbyQuery->get())->unique('id');
        }

        return $query->get();
    }

    /**
     * Mark this token as used.
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Update the device's location.
     */
    public function updateLocation(float $latitude, float $longitude): void
    {
        $this->update([
            'last_latitude' => $latitude,
            'last_longitude' => $longitude,
            'location_updated_at' => now(),
        ]);
    }

    /**
     * Deactivate this token.
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Get display name for the platform.
     */
    public function getPlatformDisplayAttribute(): string
    {
        return match ($this->platform) {
            'ios' => 'iOS',
            'android' => 'Android',
            'web' => 'Web',
            default => 'Desconocido',
        };
    }

    /**
     * Check if this token has a known location.
     */
    public function hasLocation(): bool
    {
        return $this->last_latitude !== null && $this->last_longitude !== null;
    }

    /**
     * Calculate distance to a point in kilometers.
     */
    public function distanceTo(float $latitude, float $longitude): ?float
    {
        if (! $this->hasLocation()) {
            return null;
        }

        // Haversine formula
        $earthRadius = 6371; // km

        $latFrom = deg2rad($this->last_latitude);
        $lonFrom = deg2rad($this->last_longitude);
        $latTo = deg2rad($latitude);
        $lonTo = deg2rad($longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
