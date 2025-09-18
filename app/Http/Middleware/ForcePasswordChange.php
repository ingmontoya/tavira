<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to authenticated users
        if (! auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();

        // Skip for certain routes that should always be accessible
        $allowedRoutes = [
            'password.*',
            'profile.*',
            'logout',
            'user-profile-information.update',
            'user-password.update',
            'force-password-change.show',
            'force-password-change.update',
        ];

        foreach ($allowedRoutes as $route) {
            if ($request->routeIs($route)) {
                return $next($request);
            }
        }

        // Check if user needs to change password
        // This can be determined by checking if there's a tenant with requires_password_change flag
        // or by checking if the user has a password_changed_at field that is null
        if ($this->userNeedsPasswordChange($user)) {
            return redirect()->route('force-password-change.show')
                ->with('warning', 'Debes cambiar tu contraseña antes de continuar.');
        }

        return $next($request);
    }

    /**
     * Check if user needs to change password
     */
    private function userNeedsPasswordChange($user): bool
    {
        // In tenant context, check if the tenant has the requires_password_change flag
        if (tenant()) {
            $tenantData = tenant()->data ?? [];

            return $tenantData['requires_password_change'] ?? false;
        }

        // For central users, you could implement similar logic
        // For now, we'll just return false for central users
        return false;
    }
}
