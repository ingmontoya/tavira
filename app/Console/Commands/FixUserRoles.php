<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class FixUserRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:fix-roles {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix users that are missing role assignments';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        // Find users without roles
        $usersWithoutRoles = User::whereDoesntHave('roles')->get();

        if ($usersWithoutRoles->isEmpty()) {
            $this->info('All users have roles assigned. No action needed.');
            return Command::SUCCESS;
        }

        $this->warn("Found {$usersWithoutRoles->count()} users without roles:");

        $headers = ['ID', 'Email', 'Created At', 'Tenant ID', 'Suggested Role'];
        $rows = [];

        foreach ($usersWithoutRoles as $user) {
            // Logic to determine appropriate role
            $suggestedRole = $this->getSuggestedRole($user);
            
            $rows[] = [
                $user->id,
                $user->email,
                $user->created_at->format('Y-m-d H:i:s'),
                $user->tenant_id ?? 'null',
                $suggestedRole
            ];

            if (!$dryRun) {
                $user->assignRole($suggestedRole);
                $this->info("Assigned role '{$suggestedRole}' to user {$user->email}");
            }
        }

        $this->table($headers, $rows);

        if ($dryRun) {
            $this->info('Dry run complete. Use --no-dry-run to apply changes.');
        } else {
            $this->info('Role assignment complete.');
        }

        return Command::SUCCESS;
    }

    private function getSuggestedRole(User $user): string
    {
        // Check if this is the first admin user (no tenant_id and created early)
        $hasExistingAdmins = User::role(['admin', 'superadmin'])->where('id', '!=', $user->id)->exists();
        
        if (!$user->tenant_id && !$hasExistingAdmins) {
            return 'admin';
        }

        // If user has a tenant_id, they're likely a tenant user
        if ($user->tenant_id) {
            // Check if there are admin_conjunto users for this tenant
            $hasAdminConjunto = User::role('admin_conjunto')
                ->where('tenant_id', $user->tenant_id)
                ->where('id', '!=', $user->id)
                ->exists();
            
            if (!$hasAdminConjunto) {
                return 'admin_conjunto';
            }
            
            return 'residente';
        }

        // For central users without tenant_id, default to admin if they're early users
        if (!$user->tenant_id) {
            return 'admin';
        }

        // Default fallback
        return 'residente';
    }
}