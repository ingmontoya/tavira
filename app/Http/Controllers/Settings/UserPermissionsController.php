<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserPermissionsController extends Controller
{
    public function index()
    {
        $users = User::with(['roles', 'permissions'])->get()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $user->roles,
                'permissions' => $user->permissions, // Only direct permissions
                'all_permissions' => $user->getAllPermissions()->pluck('name')->toArray(), // All permissions (role + direct)
            ];
        });
        
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            $parts = explode('_', $permission->name);

            return count($parts) > 1 ? ucfirst($parts[1]) : 'Other';
        });

        return Inertia::render('settings/UserPermissions', [
            'users' => $users,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    public function updateUserRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);


        $user->syncRoles([$request->role]);

        $user->refresh();

        // Clear permission cache for the user
        $user->forgetCachedPermissions();
        
        // Clear global permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // If updating current user's role, refresh their session permissions
        if ($user->id === auth()->id()) {
            session()->put('clear_permission_cache', true);
            // Force a complete refresh of the current user
            auth()->setUser($user->fresh(['roles.permissions', 'permissions']));
        }

        return back()->with('success', 'Rol de usuario actualizado correctamente.');
    }

    public function updateUserPermissions(Request $request, User $user)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);


        $user->syncPermissions($request->permissions ?? []);

        // Verify the permissions were assigned
        $user->refresh();

        // Clear permission cache for the user
        $user->forgetCachedPermissions();
        
        // Clear global permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // If updating current user's permissions, refresh their session permissions
        if ($user->id === auth()->id()) {
            session()->put('clear_permission_cache', true);
            // Force a complete refresh of the current user
            auth()->setUser($user->fresh(['roles.permissions', 'permissions']));
        }

        return back()->with('success', 'Permisos de usuario actualizados correctamente.');
    }

    public function updateRolePermissions(Request $request, Role $role)
    {
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role->syncPermissions($request->permissions ?? []);

        // Clear permission cache for all users with this role
        $usersWithRole = User::role($role->name)->get();
        foreach ($usersWithRole as $user) {
            $user->forgetCachedPermissions();
        }
        
        // Clear global permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // If current user has this role, refresh their session permissions
        if (auth()->user()->hasRole($role->name)) {
            session()->put('clear_permission_cache', true);
        }

        return back()->with('success', 'Permisos del rol actualizados correctamente.');
    }
}
