<?php

namespace App\Http\Middleware;

use App\Models\TenantFeature;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareTenantFeatures
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar en contexto de tenant (no en central dashboard)
        if (function_exists('tenant') && tenant()) {
            $tenant = tenant();

            try {
                // Obtener todas las features del tenant con sus configuraciones
                $featuresData = [];
                $tenant->run(function () use ($tenant, &$featuresData) {
                    $tenantFeatures = TenantFeature::where('tenant_id', $tenant->id)
                        ->where('enabled', true)
                        ->get();

                    // Crear un mapa más simple: feature => config
                    foreach ($tenantFeatures as $feature) {
                        $featuresData[$feature->feature] = [
                            'enabled' => true,
                            'config' => $feature->config ?? [],
                        ];
                    }
                });

                // Compartir con Inertia
                Inertia::share([
                    'tenant' => function () use ($tenant, $featuresData) {
                        return [
                            'id' => $tenant->id,
                            'subscription_plan' => $tenant->subscription_plan ?? 'basic',
                            'marketplace_commission' => $tenant->marketplace_commission,
                            'features' => $featuresData,
                        ];
                    },
                ]);

            } catch (\Exception $e) {
                // En caso de error, no bloquear la aplicación
                \Log::warning("Error sharing tenant features: {$e->getMessage()}", [
                    'tenant_id' => $tenant->id ?? 'unknown',
                ]);
            }
        }

        return $next($request);
    }
}
