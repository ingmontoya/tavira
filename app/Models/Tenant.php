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
}
