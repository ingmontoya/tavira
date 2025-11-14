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
            'tenant-features.*', // Allow tenant features routes (central dashboard)
            'dashboard', // Allow central dashboard access
            'home', // Allow home page access
            'provider.*', // Allow provider routes
            'admin.*', // Allow admin routes (provider registrations, etc)
            'tenant-management.*', // Allow tenant management routes
        ];

        foreach ($allowedRoutes as $route) {
            if ($request->routeIs($route)) {
                return $next($request);
            }
        }

        // Check subscription status for central operations only for regular admin users
        // Skip for superadmin and provider roles who manage multiple tenants
        if ($user->hasRole('admin') && ! $user->hasAnyRole(['superadmin', 'admin_sistema', 'provider'])) {
            // First, check if user has an active subscription
            $activeSubscription = \App\Models\TenantSubscription::where('user_id', $user->id)
                ->where('status', 'active')
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->first();

            // If user has no active subscription, redirect to subscription plans
            if (! $activeSubscription) {
                // Allow tenant-management.create route for users without subscription
                // This allows the flow: subscription → create tenant
                if (! $request->routeIs('tenant-management.create')) {
                    return redirect()->route('subscription.plans')
                        ->with('info', 'Selecciona un plan de suscripción para comenzar.');
                }
            }

            // If user has active subscription but no tenant_id, redirect to create tenant
            if ($activeSubscription && ! $user->tenant_id) {
                if (! $request->routeIs('tenant-management.*')) {
                    return redirect()->route('tenant-management.create')
                        ->with('info', 'Crea tu conjunto para comenzar.');
                }
            }
        }

        return $next($request);
    }
}
