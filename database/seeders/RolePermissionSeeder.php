<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create permissions
        $permissions = [
            // Conjunto Management
            'view_conjunto_config',
            'edit_conjunto_config',
            'view_dashboard',

            // Apartment Management
            'view_apartments',
            'create_apartments',
            'edit_apartments',
            'delete_apartments',
            'view_apartment_types',
            'create_apartment_types',
            'edit_apartment_types',
            'delete_apartment_types',

            // Resident Management
            'view_residents',
            'create_residents',
            'edit_residents',
            'delete_residents',
            'assign_residents',

            // User Management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            'manage_invitations',

            // Financial Management
            'view_payments',
            'create_payments',
            'edit_payments',
            'delete_payments',
            'view_reports',

            // Communication
            'view_announcements',
            'create_announcements',
            'edit_announcements',
            'delete_announcements',
            'send_notifications',

            // Security & Access
            'view_access_logs',
            'manage_visitors',
            'view_security_reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles
        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $admin_conjunto = Role::firstOrCreate(['name' => 'admin_conjunto']);
        $consejo = Role::firstOrCreate(['name' => 'consejo']);
        $propietario = Role::firstOrCreate(['name' => 'propietario']);
        $residente = Role::firstOrCreate(['name' => 'residente']);
        $porteria = Role::firstOrCreate(['name' => 'porteria']);

        // Assign permissions to roles
        $superadmin->givePermissionTo(Permission::all());

        $admin_conjunto->givePermissionTo([
            'view_conjunto_config', 'edit_conjunto_config',
            'view_dashboard',
            'view_apartments', 'create_apartments', 'edit_apartments', 'delete_apartments',
            'view_apartment_types', 'create_apartment_types', 'edit_apartment_types', 'delete_apartment_types',
            'view_residents', 'create_residents', 'edit_residents', 'delete_residents', 'assign_residents',
            'view_users', 'create_users', 'edit_users', 'manage_invitations',
            'view_payments', 'create_payments', 'edit_payments', 'view_reports',
            'view_announcements', 'create_announcements', 'edit_announcements', 'send_notifications',
            'view_access_logs', 'manage_visitors', 'view_security_reports',
        ]);

        $consejo->givePermissionTo([
            'view_dashboard',
            'view_apartments', 'view_apartment_types',
            'view_residents',
            'view_payments', 'view_reports',
            'view_announcements', 'create_announcements',
            'view_access_logs',
        ]);

        $propietario->givePermissionTo([
            'view_dashboard',
            'view_apartments',
            'view_residents',
            'view_payments',
            'view_announcements',
        ]);

        $residente->givePermissionTo([
            'view_dashboard',
            'view_announcements',
        ]);

        $porteria->givePermissionTo([
            'view_access_logs',
            'manage_visitors',
        ]);
    }
}
