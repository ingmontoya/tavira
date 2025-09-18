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

    protected array $definedFeatures = [];

    public function __construct()
    {
        $this->centralUrl = env('CENTRAL_URL', config('app.url'));
        $this->cacheMinutes = 5;
    }

    public function define(string $feature, callable $resolver): void
    {
        $this->definedFeatures[$feature] = $resolver;
    }

    public function defined(): array
    {
        return array_keys($this->definedFeatures);
    }

    public function getAll(array $features): array
    {
        $result = [];

        foreach ($features as $feature => $scopes) {
            $result[$feature] = [];
            foreach ($scopes as $scope) {
                $result[$feature][] = $this->get($feature, $scope);
            }
        }

        return $result;
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

    public function get(string $feature, mixed $scope): mixed
    {
        $tenantId = $this->resolveTenantId($scope);
        $cacheKey = "feature_flag:{$tenantId}:{$feature}";

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($tenantId, $feature) {
            return $this->fetchFromCentral($tenantId, $feature);
        });
    }

    public function retrieve(string $feature, mixed $scope): mixed
    {
        return $this->get($feature, $scope);
    }

    public function set(string $feature, mixed $scope, mixed $value): void
    {
        // Read-only driver - tenant apps cannot modify features
        Log::warning('Attempt to set feature flag in CentralApiDriver', [
            'feature' => $feature,
            'scope' => $scope,
            'value' => $value,
        ]);
    }

    public function setForAllScopes(string $feature, mixed $value): void
    {
        // Read-only driver - tenant apps cannot modify features
        Log::warning('Attempt to set feature flag for all scopes in CentralApiDriver', [
            'feature' => $feature,
            'value' => $value,
        ]);
    }

    public function delete(string $feature, mixed $scope): void
    {
        $tenantId = $this->resolveTenantId($scope);
        $cacheKey = "feature_flag:{$tenantId}:{$feature}";
        Cache::forget($cacheKey);

        Cache::forget("feature_flags_all:{$tenantId}");
    }

    public function purge(?array $features): void
    {
        if ($features === null) {
            // Purge all feature flags from cache
            Cache::flush();
        } else {
            // Purge specific features from cache
            foreach ($features as $feature) {
                // Since we don't know the scope, we'll use a pattern to clear all related caches
                $pattern = "feature_flag:*:{$feature}";
                // Note: Laravel's cache doesn't support pattern deletion by default
                // This is a simplified implementation
                Cache::forget($pattern);
            }
        }
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
