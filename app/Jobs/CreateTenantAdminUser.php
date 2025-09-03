<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Hash;
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
        if (!$this->tenant->admin_name || !$this->tenant->admin_email || !$this->tenant->admin_password) {
            return;
        }

        $adminUserId = null;

        $this->tenant->run(function () use (&$adminUserId) {
            // Create the admin user in the tenant database
            $user = User::create([
                'name' => $this->tenant->admin_name,
                'email' => $this->tenant->admin_email,
                'password' => Hash::make($this->tenant->admin_password),
                'email_verified_at' => now(),
            ]);

            // Store the actual user ID that was created
            $adminUserId = $user->id;
        });

        // Update the tenant with the real admin user ID (outside tenant context)
        if ($adminUserId) {
            $this->tenant->update(['admin_user_id' => $adminUserId]);
        }
    }
}