<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RequiresSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip subscription checks in development environment
        if (app()->environment('local', 'testing')) {
            return $next($request);
        }

        // Only apply to authenticated users
        if (! Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Skip for users with specific roles that don't need subscriptions
        if ($user->hasAnyRole(['superadmin', 'admin_sistema'])) {
            return $next($request);
        }

        // Determine context
        $isInTenantContext = tenancy()->initialized;
        $isCentralDomain = in_array($request->getHost(), config('tenancy.central_domains', []));

        // In tenant context, skip subscription checks entirely - tenants operate independently
        if ($isInTenantContext) {
            return $next($request);
        }

        // In central context, handle tenant creation and central operations
        if ($isCentralDomain) {
            return $this->handleCentralContext($request, $next, $user);
        }

        // Default: allow request to continue
        return $next($request);
    }

    /**
     * Handle subscription checks in central context
     */
    private function handleCentralContext(Request $request, Closure $next, $user): Response
    {
        // Skip for certain routes that should always be accessible
        $allowedRoutes = [
            'subscription.*',
            'logout',
            'verification.*',
            'password.*',
            'profile.*',
            'wompi.webhook',
            'user-profile-information.update',
            'tenant-management.*', // Allow all tenant management routes to avoid redirect loops
            'tenant-features.*', // Allow tenant features routes (central dashboard)
        ];

        foreach ($allowedRoutes as $route) {
            if ($request->routeIs($route)) {
                return $next($request);
            }
        }

        // Check subscription status for central operations
        if ($user->hasRole('admin')) {
            // If user has no tenant_id, redirect to create tenant
            if (! $user->tenant_id) {
                return redirect()->route('tenant-management.create')
                    ->with('info', 'Crea tu conjunto para comenzar.');
            }
        }

        return $next($request);
    }
}
