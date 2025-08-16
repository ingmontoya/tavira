<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MaintenancePermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create maintenance permissions
        $maintenancePermissions = [
            // Maintenance Request Management
            'view_maintenance_requests',
            'create_maintenance_requests',
            'edit_maintenance_requests',
            'delete_maintenance_requests',
            'approve_maintenance_requests',
            'assign_maintenance_requests',
            'complete_maintenance_requests',

            // Maintenance Category Management
            'view_maintenance_categories',
            'create_maintenance_categories',
            'edit_maintenance_categories',
            'delete_maintenance_categories',

            // Maintenance Staff Management
            'view_maintenance_staff',
            'create_maintenance_staff',
            'edit_maintenance_staff',
            'delete_maintenance_staff',

            // Work Order Management
            'view_work_orders',
            'create_work_orders',
            'edit_work_orders',
            'delete_work_orders',
            'assign_work_orders',
            'complete_work_orders',
        ];

        foreach ($maintenancePermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        $this->command->info('Maintenance permissions created successfully.');

        // Assign permissions to roles
        $this->assignPermissionsToRoles();
    }

    private function assignPermissionsToRoles(): void
    {
        // Admin role gets all maintenance permissions
        $adminRole = Role::where('name', 'admin_conjunto')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo([
                'view_maintenance_requests',
                'create_maintenance_requests',
                'edit_maintenance_requests',
                'delete_maintenance_requests',
                'approve_maintenance_requests',
                'assign_maintenance_requests',
                'complete_maintenance_requests',
                'view_maintenance_categories',
                'create_maintenance_categories',
                'edit_maintenance_categories',
                'delete_maintenance_categories',
                'view_maintenance_staff',
                'create_maintenance_staff',
                'edit_maintenance_staff',
                'delete_maintenance_staff',
                'view_work_orders',
                'create_work_orders',
                'edit_work_orders',
                'delete_work_orders',
                'assign_work_orders',
                'complete_work_orders',
            ]);
            $this->command->info('Maintenance permissions assigned to admin_conjunto role.');
        }

        // Council member role gets approval permissions
        $councilRole = Role::where('name', 'consejo')->first();
        if ($councilRole) {
            $councilRole->givePermissionTo([
                'view_maintenance_requests',
                'approve_maintenance_requests',
                'view_maintenance_categories',
                'view_work_orders',
            ]);
            $this->command->info('Maintenance permissions assigned to consejo role.');
        }

        // Resident role gets limited view permissions
        $residentRole = Role::where('name', 'residente')->first();
        if ($residentRole) {
            $residentRole->givePermissionTo([
                'view_maintenance_requests', // Limited to their own requests
            ]);
            $this->command->info('Limited maintenance permissions assigned to residente role.');
        }
    }
}
