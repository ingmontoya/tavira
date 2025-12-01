<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $casts = [
        'data' => 'array',
        'pending_updates' => 'array',
        'feature_usage' => 'array',
        'subscription_expires_at' => 'datetime',
        'subscription_renewed_at' => 'datetime',
        'subscription_last_checked_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'alert_radius_km' => 'decimal:2',
    ];

    protected $fillable = [
        'id',
        'data',
        'pending_updates',
        'admin_name',
        'admin_email',
        'admin_password',
        'admin_user_id',
        'subscription_status',
        'subscription_plan',
        'subscription_expires_at',
        'subscription_renewed_at',
        'subscription_last_checked_at',
        'marketplace_commission',
        'feature_usage',
        'quotation_attachment_max_size',
        // Location fields for proximity-based alerts
        'latitude',
        'longitude',
        'address',
        'city',
        'department',
        'alert_radius_km',
    ];

    protected $hidden = [
        'admin_password',
    ];

    public function features(): HasMany
    {
        return $this->hasMany(TenantFeature::class);
    }

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'admin_name',
            'admin_email',
            'admin_password',
            'admin_user_id',
        ];
    }

    /**
     * Marketplace commission rates by plan
     */
    const MARKETPLACE_COMMISSIONS = [
        'basic' => 0.08,      // 8%
        'standard' => 0.06,    // 6%
        'premium' => 0.04,     // 4%
        'enterprise' => 0.02,  // 2%
    ];

    /**
     * Get marketplace commission rate based on subscription plan
     */
    public function getMarketplaceCommissionAttribute($value): float
    {
        // If commission is set manually, use it
        if ($value !== null) {
            return (float) $value;
        }

        // Otherwise, calculate from subscription plan
        return self::MARKETPLACE_COMMISSIONS[$this->subscription_plan ?? 'basic'] ?? 0.08;
    }

    /**
     * Update commission based on plan
     */
    public function updateCommissionFromPlan(): void
    {
        $this->marketplace_commission = self::MARKETPLACE_COMMISSIONS[$this->subscription_plan ?? 'basic'] ?? 0.08;
        $this->save();
    }

    /**
     * Check if tenant has a specific feature enabled
     */
    public function hasFeature(string $feature): bool
    {
        $hasFeature = false;
        $tenantId = $this->id;

        $this->run(function () use ($feature, $tenantId, &$hasFeature) {
            $hasFeature = TenantFeature::where('tenant_id', $tenantId)
                ->where('feature', $feature)
                ->where('enabled', true)
                ->exists();
        });

        return $hasFeature;
    }

    /**
     * Get feature configuration
     */
    public function getFeatureConfig(string $feature): ?array
    {
        $config = null;
        $tenantId = $this->id;

        $this->run(function () use ($feature, $tenantId, &$config) {
            $tenantFeature = TenantFeature::where('tenant_id', $tenantId)
                ->where('feature', $feature)
                ->where('enabled', true)
                ->first();

            $config = $tenantFeature ? [
                'enabled' => true,
                'config' => $tenantFeature->config ?? [],
            ] : null;
        });

        return $config;
    }

    /**
     * Check if tenant has location coordinates.
     */
    public function hasLocation(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Get the full address string.
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->department,
        ]);

        return implode(', ', $parts) ?: 'Dirección no configurada';
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

        $latFrom = deg2rad($this->latitude);
        $lonFrom = deg2rad($this->longitude);
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

    /**
     * Get device tokens for this tenant.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    /**
     * Scope to find tenants within a radius of given coordinates.
     */
    public function scopeWithinRadius($query, float $latitude, float $longitude, float $radiusKm)
    {
        $haversine = '(
            6371 * acos(
                cos(radians(?)) *
                cos(radians(latitude)) *
                cos(radians(longitude) - radians(?)) +
                sin(radians(?)) *
                sin(radians(latitude))
            )
        )';

        return $query
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereRaw("{$haversine} <= ?", [$latitude, $longitude, $latitude, $radiusKm]);
    }

    /**
     * Get nearby tenants.
     */
    public function getNearbyTenants(float $radiusKm = 5.0)
    {
        if (! $this->hasLocation()) {
            return collect();
        }

        return static::withinRadius($this->latitude, $this->longitude, $radiusKm)
            ->where('id', '!=', $this->id)
            ->get();
    }
}
