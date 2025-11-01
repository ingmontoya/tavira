<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantFeature extends Model
{
    protected $fillable = [
        'tenant_id',
        'feature',
        'enabled',
        'config',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'config' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function isFeatureEnabled(string $tenantId, string $feature): bool
    {
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return false;
        }

        $enabled = false;
        $tenant->run(function () use ($tenantId, $feature, &$enabled) {
            $enabled = static::where('tenant_id', $tenantId)
                ->where('feature', $feature)
                ->value('enabled') ?? false;
        });

        return $enabled;
    }

    public static function enableFeature(string $tenantId, string $feature): void
    {
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return;
        }

        $tenant->run(function () use ($tenantId, $feature) {
            static::updateOrCreate(
                ['tenant_id' => $tenantId, 'feature' => $feature],
                ['enabled' => true]
            );
        });
    }

    public static function disableFeature(string $tenantId, string $feature): void
    {
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return;
        }

        $tenant->run(function () use ($tenantId, $feature) {
            static::updateOrCreate(
                ['tenant_id' => $tenantId, 'feature' => $feature],
                ['enabled' => false]
            );
        });
    }

    /**
     * Get config value for a specific key, or all config if no key provided
     */
    public function getConfig(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->config;
        }

        return data_get($this->config, $key, $default);
    }

    /**
     * Set config value for a specific key
     */
    public function setConfig(string $key, $value): self
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->config = $config;

        return $this;
    }

    /**
     * Get feature with config for a tenant
     */
    public static function getFeatureConfig(string $tenantId, string $feature): ?array
    {
        $tenant = Tenant::find($tenantId);
        if (! $tenant) {
            return null;
        }

        $config = null;
        $tenant->run(function () use ($tenantId, $feature, &$config) {
            $tenantFeature = static::where('tenant_id', $tenantId)
                ->where('feature', $feature)
                ->first();

            if ($tenantFeature && $tenantFeature->enabled) {
                $config = [
                    'enabled' => true,
                    'config' => $tenantFeature->config ?? [],
                ];
            }
        });

        return $config;
    }
}
