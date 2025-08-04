<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class SharePermissions
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            // Clear cache if requested
            if (session()->has('clear_permission_cache')) {
                session()->forget('clear_permission_cache');
                $request->user()->forgetCachedPermissions();
                // Also clear Laravel's permission cache
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            }

            // Always get fresh permissions to avoid caching issues
            $user = $request->user()->fresh(['roles.permissions', 'permissions']);
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();

            // Also get user roles
            $roles = $user->roles->pluck('name')->toArray();

            Inertia::share([
                'auth.permissions' => $permissions,
                'auth.roles' => $roles,
            ]);
        } else {
            // Make sure auth data is clean when no user
            Inertia::share([
                'auth.permissions' => [],
                'auth.roles' => [],
            ]);
        }

        return $next($request);
    }
}
