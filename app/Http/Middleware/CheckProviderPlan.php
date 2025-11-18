<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckProviderPlan
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply in CENTRAL domain
        if (! $this->isCentralDomain()) {
            return $next($request);
        }

        $user = $request->user();

        // Only for providers
        if (! $user || ! $user->hasRole('provider')) {
            return $next($request);
        }

        // Get provider profile
        $provider = $user->provider;

        if (! $provider) {
            abort(403, 'No tienes un perfil de proveedor asociado.');
        }

        // Exclude pricing and payment routes from check
        if ($request->routeIs('provider.pricing') ||
            $request->routeIs('provider.pricing-viewed') ||
            $request->routeIs('provider.subscribe') ||
            $request->routeIs('provider.payment.*') ||
            $request->routeIs('logout')) {
            return $next($request);
        }

        // If provider hasn't seen pricing modal, redirect to pricing
        if (! $provider->has_seen_pricing) {
            return redirect()->route('provider.pricing');
        }

        return $next($request);
    }

    /**
     * Check if current request is on central domain.
     */
    private function isCentralDomain(): bool
    {
        $host = request()->getHost();
        $centralDomains = config('tenancy.central_domains', []);

        // Check if host matches any central domain
        foreach ($centralDomains as $centralDomain) {
            if ($host === $centralDomain || str_ends_with($host, ".{$centralDomain}")) {
                return true;
            }
        }

        return false;
    }
}
