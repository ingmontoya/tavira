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
        ];

        foreach ($allowedRoutes as $route) {
            if ($request->routeIs($route)) {
                return $next($request);
            }
        }

        // Check if user is an admin without tenant (central admin)
        if ($user->hasRole('admin') && !$user->tenant_id) {
            // Check if user has an active subscription
            $activeSubscription = TenantSubscription::where(function ($query) use ($user) {
                $query->whereNull('tenant_id') // For new signups
                      ->orWhere('tenant_id', $user->tenant_id);
            })
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first();

            // Log subscription check for debugging
            \Illuminate\Support\Facades\Log::info('RequiresSubscription middleware check:', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'tenant_id' => $user->tenant_id,
                'has_admin_role' => $user->hasRole('admin'),
                'active_subscription_found' => !is_null($activeSubscription),
                'subscription_id' => $activeSubscription?->id,
                'request_route' => $request->route()?->getName(),
            ]);

            // If no active subscription, redirect to plan selection
            if (!$activeSubscription) {
                \Illuminate\Support\Facades\Log::warning('User redirected to plans - no active subscription found', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                ]);
                
                return redirect()->route('subscription.plans')
                    ->with('info', 'Para continuar, debes seleccionar y pagar un plan de suscripción.');
            }

            // If subscription exists but no tenant, create the tenant
            if ($activeSubscription && !$user->tenant_id) {
                \Illuminate\Support\Facades\Log::info('Creating tenant for user with active subscription', [
                    'user_id' => $user->id,
                    'subscription_id' => $activeSubscription->id,
                    'user_current_tenant_id' => $user->tenant_id,
                ]);
                
                $this->createTenantForUser($user, $activeSubscription);
                
                // Reload user to get updated tenant_id
                $user->refresh();
                
                \Illuminate\Support\Facades\Log::info('Tenant creation completed', [
                    'user_id' => $user->id,
                    'user_new_tenant_id' => $user->tenant_id,
                ]);
            }
        }

        return $next($request);
    }

    /**
     * Create a tenant for the user based on their active subscription
     */
    private function createTenantForUser($user, $subscription)
    {
        try {
            // Get stored subscription data from payment
            $paymentData = $subscription->payment_data;
            
            if (!$paymentData || !isset($paymentData['conjunto_name'])) {
                // If no payment data, use default values based on user info
                \Illuminate\Support\Facades\Log::info('No complete payment data found, using defaults for tenant creation', [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'payment_data' => $paymentData
                ]);
                
                $paymentData = [
                    'conjunto_name' => 'Conjunto ' . $user->name,
                    'conjunto_address' => null,
                    'city' => 'Bogotá',
                    'region' => 'Bogotá D.C.',
                    'customer_name' => $user->name,
                    'customer_email' => $user->email,
                ];
            }

            // Create tenant
            $tenant = \App\Models\Tenant::create([
                'name' => $paymentData['conjunto_name'],
                'data' => [
                    'address' => $paymentData['conjunto_address'] ?? null,
                    'city' => $paymentData['city'] ?? 'Bogotá',
                    'region' => $paymentData['region'] ?? 'Bogotá D.C.',
                    'plan' => $subscription->plan_name,
                    'subscription_id' => $subscription->id,
                ],
            ]);

            // Update user with tenant association
            $user->update(['tenant_id' => $tenant->id]);

            // Update subscription with tenant_id
            $subscription->update(['tenant_id' => $tenant->id]);

            // Log tenant creation
            \Illuminate\Support\Facades\Log::info('Tenant created for user after subscription payment', [
                'user_id' => $user->id,
                'tenant_id' => $tenant->id,
                'subscription_id' => $subscription->id,
            ]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error creating tenant for user', [
                'user_id' => $user->id,
                'subscription_id' => $subscription->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
