<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create roles for central administration
        $superadmin = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        $admin = Role::create(['name' => 'admin', 'guard_name' => 'web']);

        // Create roles that will also exist in tenants
        $adminConjunto = Role::create(['name' => 'admin_conjunto', 'guard_name' => 'web']);
        $residente = Role::create(['name' => 'residente', 'guard_name' => 'web']);
        $portero = Role::create(['name' => 'portero', 'guard_name' => 'web']);
        $provider = Role::create(['name' => 'provider', 'guard_name' => 'web']);

        // Create basic permissions
        $permissions = [
            // Tenant management (central only)
            'manage_tenants',
            'create_tenants',
            'view_tenants',
            'edit_tenants',
            'delete_tenants',

            // User management
            'manage_users',
            'create_users',
            'view_users',
            'edit_users',
            'delete_users',

            // General permissions
            'access_dashboard',
            'manage_settings',

            // Provider permissions
            'view_quotation_requests',
            'submit_proposals',
            'manage_service_catalog',
            'view_provider_dashboard',
            'manage_provider_services',
            'view_provider_proposals',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission, 'guard_name' => 'web']);
        }

        // Assign permissions to superadmin (all permissions)
        $superadmin->givePermissionTo(Permission::all());

        // Assign permissions to admin (tenant management)
        $admin->givePermissionTo([
            'manage_tenants',
            'create_tenants',
            'view_tenants',
            'edit_tenants',
            'access_dashboard',
        ]);

        // Assign basic permissions to admin_conjunto
        $adminConjunto->givePermissionTo([
            'access_dashboard',
            'manage_users',
            'view_users',
            'manage_settings',
        ]);

        // Assign permissions to provider
        $provider->givePermissionTo([
            'view_provider_dashboard',
            'view_quotation_requests',
            'submit_proposals',
            'manage_service_catalog',
            'manage_provider_services',
            'view_provider_proposals',
        ]);
    }
}
