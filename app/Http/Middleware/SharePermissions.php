<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class SharePermissions
{
    /**
     * List of superadmin emails for central context.
     * These users get superadmin role when accessing the central dashboard.
     */
    private const CENTRAL_SUPERADMINS = [
        'mauricio.montoyav@gmail.com',
        'hola@tavira.com.co',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $permissions = [];
            $roles = [];

            // Check if we're in tenant context
            // The roles/permissions tables only exist in tenant databases
            if (tenancy()->initialized) {
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
            } else {
                // Central context - no Spatie tables available
                // Assign superadmin role based on email
                if (in_array($request->user()->email, self::CENTRAL_SUPERADMINS)) {
                    $roles = ['superadmin'];
                    // Grant all central permissions for superadmin
                    $permissions = ['view_dashboard'];
                }
            }

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
