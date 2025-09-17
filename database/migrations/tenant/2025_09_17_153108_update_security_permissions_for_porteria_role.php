<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create new security permissions if they don't exist
        $newPermissions = [
            'view_security_alerts',
            'manage_security_alerts',
            'respond_to_panic_alerts',
            'resolve_security_incidents',
            'view_panic_alerts',
            'acknowledge_panic_alerts',
            'resolve_panic_alerts',
        ];

        foreach ($newPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Update porteria role permissions
        $porteria = Role::where('name', 'porteria')->first();
        if ($porteria) {
            $porteria->givePermissionTo([
                'view_dashboard',
                'view_access_logs',
                'manage_visitors',
                'view_correspondence',
                'create_correspondence',
                'edit_correspondence',
                'deliver_correspondence',
                'manage_correspondence_attachments',
                'view_security_alerts',
                'manage_security_alerts',
                'respond_to_panic_alerts',
                'resolve_security_incidents',
                'view_panic_alerts',
                'acknowledge_panic_alerts',
                'resolve_panic_alerts',
                'view_security_reports',
            ]);
        }

        // Update admin_conjunto role permissions
        $adminConjunto = Role::where('name', 'admin_conjunto')->first();
        if ($adminConjunto) {
            $adminConjunto->givePermissionTo($newPermissions);
        }

        // Update consejo role permissions (view only)
        $consejo = Role::where('name', 'consejo')->first();
        if ($consejo) {
            $consejo->givePermissionTo([
                'view_security_alerts',
                'view_panic_alerts',
                'acknowledge_panic_alerts',
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove new permissions from roles
        $newPermissions = [
            'view_security_alerts',
            'manage_security_alerts',
            'respond_to_panic_alerts',
            'resolve_security_incidents',
            'view_panic_alerts',
            'acknowledge_panic_alerts',
            'resolve_panic_alerts',
        ];

        // Remove permissions from porteria role
        $porteria = Role::where('name', 'porteria')->first();
        if ($porteria) {
            $porteria->revokePermissionTo($newPermissions);
        }

        // Remove permissions from admin_conjunto role
        $adminConjunto = Role::where('name', 'admin_conjunto')->first();
        if ($adminConjunto) {
            $adminConjunto->revokePermissionTo($newPermissions);
        }

        // Remove view permissions from consejo role
        $consejo = Role::where('name', 'consejo')->first();
        if ($consejo) {
            $consejo->revokePermissionTo([
                'view_security_alerts',
                'view_panic_alerts',
                'acknowledge_panic_alerts',
            ]);
        }

        // Delete the permissions (optional - be careful with this)
        // Permission::whereIn('name', $newPermissions)->delete();
    }
};
