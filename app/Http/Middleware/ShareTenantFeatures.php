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
            $tenantId = tenant('id');

            try {
                // Obtener todas las features del tenant
                $tenantFeatures = TenantFeature::where('tenant_id', $tenantId)->get();

                // Compartir con Inertia
                Inertia::share([
                    'tenant' => function () use ($tenantId, $tenantFeatures) {
                        return [
                            'id' => $tenantId,
                            'features' => $tenantFeatures->toArray(),
                        ];
                    },
                ]);

            } catch (\Exception $e) {
                // En caso de error, no bloquear la aplicación
                \Log::warning("Error sharing tenant features: {$e->getMessage()}", [
                    'tenant_id' => $tenantId,
                ]);
            }
        }

        return $next($request);
    }
}
