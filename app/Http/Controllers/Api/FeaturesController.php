<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TenantFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class FeaturesController extends Controller
{
    public function show(string $tenantId, string $feature): JsonResponse
    {
        $cacheKey = "tenant_feature_{$tenantId}_{$feature}";
        
        $enabled = Cache::remember($cacheKey, 300, function () use ($tenantId, $feature) {
            return TenantFeature::isFeatureEnabled($tenantId, $feature);
        });

        return response()->json([
            'feature' => $feature,
            'enabled' => $enabled,
            'tenant_id' => $tenantId,
        ]);
    }

    public function index(string $tenantId): JsonResponse
    {
        $cacheKey = "tenant_features_{$tenantId}";
        
        $features = Cache::remember($cacheKey, 300, function () use ($tenantId) {
            return TenantFeature::where('tenant_id', $tenantId)
                ->get(['feature', 'enabled'])
                ->keyBy('feature')
                ->map->enabled
                ->toArray();
        });

        return response()->json([
            'tenant_id' => $tenantId,
            'features' => $features,
        ]);
    }

    public function invalidateCache(string $tenantId, ?string $feature = null): void
    {
        if ($feature) {
            Cache::forget("tenant_feature_{$tenantId}_{$feature}");
        } else {
            Cache::forget("tenant_features_{$tenantId}");
            $features = TenantFeature::where('tenant_id', $tenantId)->pluck('feature');
            foreach ($features as $f) {
                Cache::forget("tenant_feature_{$tenantId}_{$f}");
            }
        }
    }
}
