<?php

namespace App\Http\Middleware;

use App\Models\TenantSubscription;
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
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Skip for users with specific roles that don't need subscriptions
        if ($user->hasAnyRole(['superadmin', 'admin_sistema'])) {
            return $next($request);
        }

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
        ];

        foreach ($allowedRoutes as $route) {
            if ($request->routeIs($route)) {
                return $next($request);
            }
        }

        // Check subscription status based on user role and context
        if ($user->hasRole('admin')) {
            // If user has no tenant_id, redirect to create tenant
            if (!$user->tenant_id) {
                return redirect()->route('tenant-management.create')
                    ->with('info', 'Crea tu conjunto para comenzar.');
            }

            // If in tenant context, check the tenant's subscription status
            if (tenancy()->tenant) {
                $tenant = tenancy()->tenant;
                
                // Check if tenant subscription is active
                if (!in_array($tenant->subscription_status, ['active'])) {
                    \Illuminate\Support\Facades\Log::warning('User redirected to plans - tenant subscription not active', [
                        'user_id' => $user->id,
                        'tenant_id' => $tenant->id,
                        'subscription_status' => $tenant->subscription_status,
                    ]);
                    
                    return redirect()->route('subscription.plans')
                        ->with('warning', 'Tu suscripción no está activa. Renueva para continuar.');
                }

                // Check if subscription is expired
                if ($tenant->subscription_expires_at && $tenant->subscription_expires_at->isPast()) {
                    \Illuminate\Support\Facades\Log::warning('User redirected to plans - tenant subscription expired', [
                        'user_id' => $user->id,
                        'tenant_id' => $tenant->id,
                        'expires_at' => $tenant->subscription_expires_at,
                    ]);
                    
                    return redirect()->route('subscription.plans')
                        ->with('warning', 'Tu suscripción ha expirado. Renueva para continuar.');
                }
            }
        }

        return $next($request);
    }
}
