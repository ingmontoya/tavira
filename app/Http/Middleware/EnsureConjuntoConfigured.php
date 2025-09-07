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

        // Since multitenancy was removed, we always check for conjunto configuration
        // For central/superadmin users, bypass conjunto requirement
        $user = auth()->user();
        if ($user && $user->hasRole('superadmin')) {
            Inertia::share([
                'conjuntoConfigured' => [
                    'exists' => true, // Superadmin always has access
                    'isActive' => true,
                    'name' => 'Central Administration',
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
