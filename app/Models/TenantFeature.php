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
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function isFeatureEnabled(string $tenantId, string $feature): bool
    {
        return static::where('tenant_id', $tenantId)
            ->where('feature', $feature)
            ->value('enabled') ?? false;
    }

    public static function enableFeature(string $tenantId, string $feature): void
    {
        static::updateOrCreate(
            ['tenant_id' => $tenantId, 'feature' => $feature],
            ['enabled' => true]
        );
    }

    public static function disableFeature(string $tenantId, string $feature): void
    {
        static::updateOrCreate(
            ['tenant_id' => $tenantId, 'feature' => $feature],
            ['enabled' => false]
        );
    }
}
