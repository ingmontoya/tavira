<?php

namespace App\Http\Middleware;

use App\Models\ConjuntoConfig;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureConjuntoConfigured
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply this middleware to authenticated routes
        if (! auth()->check()) {
            return $next($request);
        }

        // Check if we're in a tenant context by safely checking if tenant() function exists and returns a value
        $isTenantContext = false;
        if (function_exists('tenant')) {
            try {
                $isTenantContext = tenant() !== null;
            } catch (\Exception $e) {
                // If tenant() throws an exception (like database not configured), we're not in tenant context
                $isTenantContext = false;
            }
        }
        
        // If we're NOT in a tenant context (i.e., we're in central domain), skip this middleware
        if (!$isTenantContext) {
            // Share default values for central domain
            Inertia::share([
                'conjuntoConfigured' => [
                    'exists' => false,
                    'isActive' => false,
                    'name' => null,
                ],
            ]);
            return $next($request);
        }

        $conjunto = ConjuntoConfig::first();

        // Share the conjunto configuration status with all Inertia responses
        Inertia::share([
            'conjuntoConfigured' => [
                'exists' => $conjunto !== null,
                'isActive' => $conjunto ? $conjunto->is_active : false,
                'name' => $conjunto ? $conjunto->name : null,
            ],
        ]);

        // Allow access to specific routes even if no conjunto is configured
        $allowedRoutes = [
            'conjunto-config.*',
            'logout',
            'profile.*',
            'settings.*',
            'dashboard', // Allow dashboard access
            'verification.*', // Allow email verification routes
            'tenant-management.*', // Allow tenant management routes (central dashboard)
            'tenant-features.*', // Allow tenant features routes (central dashboard)
        ];

        foreach ($allowedRoutes as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // If no conjunto is configured, redirect to dashboard
        if (! $conjunto) {
            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
