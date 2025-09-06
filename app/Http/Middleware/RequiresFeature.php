<?php

namespace App\Http\Middleware;

use App\Models\TenantFeature;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequiresFeature
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        // En contexto multitenancy, usar tenant() helper
        // En contexto central dashboard, no aplicar restricciones
        if (!function_exists('tenant') || !tenant()) {
            return $next($request);
        }

        $tenantId = tenant('id');
        
        if (!TenantFeature::isFeatureEnabled($tenantId, $feature)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Esta funcionalidad no está disponible en su plan.',
                    'feature' => $feature,
                    'enabled' => false,
                ], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'Esta funcionalidad no está disponible en su plan.');
        }

        return $next($request);
    }
}