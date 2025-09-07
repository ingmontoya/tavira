<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use App\Models\User;

class CleanAdminConjuntoRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:clean-admin-conjunto {--force : Force the operation to run in production}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up admin_conjunto role by migrating users and permissions to admin role';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (app()->environment('production') && !$this->option('force')) {
            $this->error('This command is running in production. Use --force to proceed.');
            return 1;
        }

        $this->info('Starting admin_conjunto role cleanup...');

        try {
            // Find the roles
            $adminConjuntoRole = Role::where('name', 'admin_conjunto')->first();
            $adminRole = Role::where('name', 'admin')->first();

            if (!$adminConjuntoRole) {
                $this->info('admin_conjunto role not found. Nothing to clean.');
                return 0;
            }

            if (!$adminRole) {
                $this->error('admin role not found. Cannot proceed.');
                return 1;
            }

            // Transfer permissions
            $permissions = $adminConjuntoRole->permissions;
            $this->info("Transferring {$permissions->count()} permissions from admin_conjunto to admin...");
            
            foreach ($permissions as $permission) {
                if (!$adminRole->hasPermissionTo($permission)) {
                    $adminRole->givePermissionTo($permission);
                }
            }

            // Migrate users
            $users = User::role('admin_conjunto')->get();
            $this->info("Migrating {$users->count()} users from admin_conjunto to admin...");

            foreach ($users as $user) {
                $this->line("  - Migrating: {$user->email}");
                
                // Remove admin_conjunto role
                $user->removeRole('admin_conjunto');
                
                // Add admin role if not already present
                if (!$user->hasRole('admin')) {
                    $user->assignRole('admin');
                    $this->line("    ✓ Assigned admin role");
                } else {
                    $this->line("    ✓ Already has admin role");
                }
            }

            // Delete the admin_conjunto role
            $adminConjuntoRole->delete();
            $this->info('✓ admin_conjunto role deleted successfully');

            // Clear permission cache
            $this->call('permission:cache-reset');
            
            $this->info('✅ Admin conjunto role cleanup completed successfully!');
            
            return 0;

        } catch (\Exception $e) {
            $this->error("Error during cleanup: {$e->getMessage()}");
            return 1;
        }
    }
}
