<?php

namespace App\Services\FeatureFlags;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Pennant\Contracts\Driver;

class CentralApiDriver implements Driver
{
    protected string $centralUrl;
    protected int $cacheMinutes;

    public function __construct()
    {
        $this->centralUrl = env('CENTRAL_URL', config('app.url'));
        $this->cacheMinutes = 5;
    }

    public function retrieve(string $feature, mixed $scope): mixed
    {
        $tenantId = $this->resolveTenantId($scope);
        $cacheKey = "feature_flag:{$tenantId}:{$feature}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($tenantId, $feature) {
            return $this->fetchFromCentral($tenantId, $feature);
        });
    }

    public function retrieveAll(array $features, mixed $scope): array
    {
        $tenantId = $this->resolveTenantId($scope);
        $cacheKey = "feature_flags_all:{$tenantId}";

        $allFeatures = Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($tenantId) {
            return $this->fetchAllFromCentral($tenantId);
        });

        $result = [];
        foreach ($features as $feature) {
            $result[$feature] = $allFeatures[$feature] ?? false;
        }

        return $result;
    }

    public function store(string $feature, mixed $scope, mixed $value): void
    {
        // Read-only driver - tenant apps cannot modify features
        Log::warning('Attempt to store feature flag in CentralApiDriver', [
            'feature' => $feature,
            'scope' => $scope,
            'value' => $value,
        ]);
    }

    public function forget(string $feature, mixed $scope): void
    {
        $tenantId = $this->resolveTenantId($scope);
        $cacheKey = "feature_flag:{$tenantId}:{$feature}";
        Cache::forget($cacheKey);

        Cache::forget("feature_flags_all:{$tenantId}");
    }

    public function forgetAll(array $features, mixed $scope): void
    {
        $tenantId = $this->resolveTenantId($scope);
        
        foreach ($features as $feature) {
            Cache::forget("feature_flag:{$tenantId}:{$feature}");
        }
        
        Cache::forget("feature_flags_all:{$tenantId}");
    }

    protected function fetchFromCentral(string $tenantId, string $feature): bool
    {
        try {
            $url = "{$this->centralUrl}/api/internal/features/{$tenantId}/{$feature}";
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return $data['enabled'] ?? false;
            }

            Log::warning('Failed to fetch feature flag from central', [
                'tenant_id' => $tenantId,
                'feature' => $feature,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Exception fetching feature flag from central', [
                'tenant_id' => $tenantId,
                'feature' => $feature,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function fetchAllFromCentral(string $tenantId): array
    {
        try {
            $url = "{$this->centralUrl}/api/internal/features/{$tenantId}";
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $data = $response->json();
                return $data['features'] ?? [];
            }

            Log::warning('Failed to fetch all feature flags from central', [
                'tenant_id' => $tenantId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Exception fetching all feature flags from central', [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    protected function resolveTenantId(mixed $scope): string
    {
        if (is_string($scope)) {
            return $scope;
        }

        if (function_exists('tenant')) {
            $tenant = tenant();
            if ($tenant) {
                return $tenant->getTenantKey();
            }
        }

        if (app()->bound('tenant.id')) {
            return app('tenant.id');
        }

        throw new \RuntimeException('Unable to resolve tenant ID for feature flag lookup');
    }
}