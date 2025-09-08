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

            // Expense Management
            'view_expenses',
            'create_expenses',
            'edit_expenses',
            'delete_expenses',
            'approve_expenses',
            'manage_expense_categories',

            // Accounting Management
            'view_accounting',
            'create_accounting',
            'edit_accounting',
            'delete_accounting',
            'approve_accounting',
            'manage_accounting',

            // Budget Management
            'view_budgets',
            'create_budgets',
            'edit_budgets',
            'delete_budgets',
            'approve_budgets',
            'activate_budgets',
            'close_budgets',

            // Communication
            'view_announcements',
            'create_announcements',
            'edit_announcements',
            'delete_announcements',
            'send_notifications',

            // Correspondence Management
            'view_correspondence',
            'create_correspondence',
            'edit_correspondence',
            'delete_correspondence',
            'deliver_correspondence',
            'manage_correspondence_attachments',

            // Email Management
            'view_admin_email',
            'create_admin_email',
            'edit_admin_email',
            'delete_admin_email',
            'view_council_email',
            'create_council_email',
            'edit_council_email',
            'delete_council_email',
            'manage_email_templates',

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

            // Reservations Management
            'view_reservations',
            'create_reservations',
            'edit_reservations',
            'delete_reservations',
            'manage_reservable_assets',
            'approve_reservations',

            // Assembly Management
            'view_assemblies',
            'create_assemblies',
            'edit_assemblies',
            'delete_assemblies',
            'start_assemblies',
            'close_assemblies',
            'manage_assembly_attendance',
            'view_assembly_votes',
            'create_assembly_votes',
            'edit_assembly_votes',
            'delete_assembly_votes',
            'participate_in_assemblies',
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
            'view_expenses', 'create_expenses', 'edit_expenses', 'delete_expenses', 'approve_expenses', 'manage_expense_categories',
            'view_accounting', 'create_accounting', 'edit_accounting', 'delete_accounting', 'approve_accounting', 'manage_accounting',
            'view_budgets', 'create_budgets', 'edit_budgets', 'delete_budgets', 'activate_budgets', 'close_budgets',
            'view_announcements', 'create_announcements', 'edit_announcements', 'send_notifications',
            'view_correspondence', 'create_correspondence', 'edit_correspondence', 'delete_correspondence', 'deliver_correspondence', 'manage_correspondence_attachments',
            'view_admin_email', 'create_admin_email', 'edit_admin_email', 'delete_admin_email', 'manage_email_templates', // Admin solo puede ver su email
            'view_access_logs', 'manage_visitors', 'view_security_reports',
            'view_reservations', 'create_reservations', 'edit_reservations', 'delete_reservations', 'manage_reservable_assets', 'approve_reservations',
            'view_assemblies', 'create_assemblies', 'edit_assemblies', 'delete_assemblies', 'start_assemblies', 'close_assemblies', 'manage_assembly_attendance',
            'view_assembly_votes', 'create_assembly_votes', 'edit_assembly_votes', 'delete_assembly_votes', 'participate_in_assemblies',
        ]);

        $consejo->givePermissionTo([
            'view_dashboard', 'view_account_statement',
            'view_apartments', 'view_apartment_types',
            'view_residents',
            'view_payments', 'view_reports',
            'view_expenses', 'create_expenses', 'edit_expenses', 'approve_expenses',
            'view_accounting', 'create_accounting', 'edit_accounting', 'manage_accounting',
            'view_budgets', 'approve_budgets', // Council can view and approve budgets only
            'view_announcements', 'create_announcements',
            'view_correspondence', // Mantienen correspondencia física
            'view_council_email', 'create_council_email', 'edit_council_email', 'delete_council_email', 'manage_email_templates', // Concejo solo puede ver su email
            'view_access_logs',
            'invite_visitors', 'receive_notifications', 'send_pqrs', 'send_messages_to_admin',
            'review_provider_proposals',
            'view_reservations', 'create_reservations', 'edit_reservations', 'approve_reservations',
            'view_assemblies', 'create_assemblies', 'edit_assemblies', 'start_assemblies', 'close_assemblies',
            'view_assembly_votes', 'create_assembly_votes', 'participate_in_assemblies',
        ]);

        $propietario->givePermissionTo([
            'view_dashboard', 'view_account_statement',
            'view_announcements',
            'view_correspondence',
            'invite_visitors', 'receive_notifications', 'send_pqrs', 'send_messages_to_admin',
            'view_reservations', 'create_reservations', 'edit_reservations',
            'view_assemblies', 'participate_in_assemblies', 'view_assembly_votes',
        ]);

        $residente->givePermissionTo([
            'view_dashboard', 'view_account_statement',
            'view_announcements',
            'view_correspondence',
            'invite_visitors', 'receive_notifications', 'send_pqrs', 'send_messages_to_admin',
            'view_reservations', 'create_reservations', 'edit_reservations',
            'view_assemblies', 'participate_in_assemblies', 'view_assembly_votes',
        ]);

        $porteria->givePermissionTo([
            'view_access_logs',
            'manage_visitors',
            'view_correspondence', 'create_correspondence', 'edit_correspondence', 'deliver_correspondence', 'manage_correspondence_attachments',
        ]);
    }
}
