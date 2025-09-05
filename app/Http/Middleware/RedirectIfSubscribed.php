<?php

namespace App\Http\Middleware;

use App\Models\TenantSubscription;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfSubscribed
{
    /**
     * Handle an incoming request.
     *
     * Redirects authenticated users who already have an active subscription
     * away from the subscription plans page to the dashboard.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip in development environment
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

        // Check if user already has an active subscription
        $activeSubscription = TenantSubscription::where(function ($query) use ($user) {
            $query->where('user_id', $user->id)
                  ->orWhere(function ($q) use ($user) {
                      $q->whereNull('tenant_id')->whereNull('user_id'); // New signups
                  });
        })
        ->where('status', 'active')
        ->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        })
        ->first();

        // Log subscription check for debugging
        Log::info('RedirectIfSubscribed middleware check:', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'tenant_id' => $user->tenant_id,
            'has_admin_role' => $user->hasRole('admin'),
            'active_subscription_found' => !is_null($activeSubscription),
            'subscription_id' => $activeSubscription?->id,
            'request_route' => $request->route()?->getName(),
        ]);

        // If user has active subscription, redirect to dashboard
        if ($activeSubscription) {
            Log::info('User with active subscription redirected from plans to dashboard', [
                'user_id' => $user->id,
                'subscription_id' => $activeSubscription->id,
                'from_route' => $request->route()?->getName(),
            ]);
            
            return redirect()->route('dashboard')
                ->with('info', 'Ya tienes una suscripción activa. ¡Bienvenido de vuelta!')
                ->with('subscription', $activeSubscription);
        }

        return $next($request);
    }
}