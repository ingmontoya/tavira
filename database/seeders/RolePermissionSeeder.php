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

            // Accounting Management
            'view_accounting',
            'create_accounting',
            'edit_accounting',
            'delete_accounting',
            'approve_accounting',

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

            // New modules for residents/propietarios
            'view_account_statement',
            'invite_visitors',
            'receive_notifications',
            'send_pqrs',
            'send_messages_to_admin',

            // Council/Consejo specific
            'review_provider_proposals',
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
            'view_accounting', 'create_accounting', 'edit_accounting', 'delete_accounting', 'approve_accounting',
            'view_announcements', 'create_announcements', 'edit_announcements', 'send_notifications',
            'view_access_logs', 'manage_visitors', 'view_security_reports',
        ]);

        $consejo->givePermissionTo([
            'view_dashboard', 'view_account_statement',
            'view_apartments', 'view_apartment_types',
            'view_residents',
            'view_payments', 'view_reports',
            'view_accounting', 'create_accounting', 'edit_accounting',
            'view_announcements', 'create_announcements',
            'view_access_logs',
            'invite_visitors', 'receive_notifications', 'send_pqrs', 'send_messages_to_admin',
            'review_provider_proposals',
        ]);

        $propietario->givePermissionTo([
            'view_dashboard', 'view_account_statement',
            'view_announcements',
            'invite_visitors', 'receive_notifications', 'send_pqrs', 'send_messages_to_admin',
        ]);

        $residente->givePermissionTo([
            'view_dashboard', 'view_account_statement',
            'view_announcements',
            'invite_visitors', 'receive_notifications', 'send_pqrs', 'send_messages_to_admin',
        ]);

        $porteria->givePermissionTo([
            'view_access_logs',
            'manage_visitors',
        ]);
    }
}
