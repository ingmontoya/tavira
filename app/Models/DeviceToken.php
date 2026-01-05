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
        'user_type',
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
     * Scope to get tokens for SecurityPersonnel users.
     */
    public function scopeForSecurityPersonnel($query)
    {
        return $query->where('user_type', 'security_personnel');
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
     *
     * This method returns tokens for:
     * 1. All users with devices in the same tenant (porteria, admin, residents)
     * 2. SecurityPersonnel users (police) - these have user_type='security_personnel' and tenant_id=NULL
     * 3. Users from nearby tenants (within alert radius) if coordinates are available
     *
     * @param  string  $tenantId  The tenant where the alert originated
     * @param  float|null  $alertLatitude  Alert latitude for proximity search
     * @param  float|null  $alertLongitude  Alert longitude for proximity search
     * @return \Illuminate\Support\Collection
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

        $radius = $tenant->alert_radius_km ?? 5.0;
        $allTokens = collect();

        // 1. Get tokens from users in the same tenant (porteria, residents, admins)
        $tenantTokens = static::active()->where('tenant_id', $tenantId)->get();
        $allTokens = $allTokens->merge($tenantTokens);

        // 2. Get tokens from SecurityPersonnel (police users with user_type='security_personnel')
        // These users don't have tenant_id as they're global/central users
        $securityPersonnelTokens = static::active()
            ->forSecurityPersonnel()
            ->get();

        // If we have alert coordinates, filter SecurityPersonnel by proximity
        if ($alertLatitude && $alertLongitude && $securityPersonnelTokens->isNotEmpty()) {
            // Get only SecurityPersonnel tokens within radius
            $nearbySecurityTokens = static::active()
                ->forSecurityPersonnel()
                ->withinRadius($alertLatitude, $alertLongitude, $radius)
                ->get();

            // Also include SecurityPersonnel tokens without location (they might be new)
            $tokensWithoutLocation = $securityPersonnelTokens->filter(function ($token) {
                return ! $token->hasLocation();
            });

            $allTokens = $allTokens->merge($nearbySecurityTokens)->merge($tokensWithoutLocation);
        } else {
            // No coordinates - include all SecurityPersonnel tokens
            $allTokens = $allTokens->merge($securityPersonnelTokens);
        }

        // 3. Get tokens from nearby tenants if we have coordinates
        if ($alertLatitude && $alertLongitude && $tenant->latitude && $tenant->longitude) {
            $nearbyQuery = static::active()
                ->withinRadius($alertLatitude, $alertLongitude, $radius)
                ->where('tenant_id', '!=', $tenantId)
                ->whereNotNull('tenant_id'); // Exclude SecurityPersonnel (already included above)

            $allTokens = $allTokens->merge($nearbyQuery->get());
        }

        return $allTokens->unique('id');
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
