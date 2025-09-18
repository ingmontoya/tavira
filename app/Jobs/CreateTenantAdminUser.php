<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Contracts\Tenant;

class CreateTenantAdminUser implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Tenant $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    public function handle(): void
    {
        // Only create user if credentials are provided
        if (! $this->tenant->admin_name || ! $this->tenant->admin_email || ! $this->tenant->admin_password) {
            return;
        }

        $adminUserId = null;

        $this->tenant->run(function () use (&$adminUserId) {
            // Create the admin user in the tenant database
            $user = User::create([
                'name' => $this->tenant->admin_name,
                'email' => $this->tenant->admin_email,
                'password' => $this->tenant->admin_password, // Password is already hashed in controller
                'email_verified_at' => now(),
            ]);

            // Assign admin role to the user (assuming roles are seeded)
            if (class_exists(\Spatie\Permission\Models\Role::class)) {
                try {
                    $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
                    $user->assignRole($adminRole);
                } catch (\Exception $e) {
                    // Role assignment failed, but user is created
                    logger()->warning('Failed to assign admin role to tenant user: '.$e->getMessage());
                }
            }

            // Store the actual user ID that was created
            $adminUserId = $user->id;
        });

        // Update the tenant with the real admin user ID (outside tenant context)
        if ($adminUserId) {
            $this->tenant->update(['admin_user_id' => $adminUserId]);
        }
    }
}
