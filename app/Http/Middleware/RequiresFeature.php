<?php

namespace App\Http\Middleware;

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
        if (! function_exists('tenant') || ! tenant()) {
            return $next($request);
        }

        $tenant = tenant();

        if (! $tenant->hasFeature($feature)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Esta funcionalidad no está disponible en su plan.',
                    'feature' => $feature,
                    'enabled' => false,
                    'current_plan' => $tenant->subscription_plan,
                    'upgrade_required' => true,
                ], 403);
            }

            return redirect()->route('dashboard')
                ->with('error', 'Esta funcionalidad no está disponible en su plan. Contacta a soporte para actualizar.');
        }

        return $next($request);
    }
}
