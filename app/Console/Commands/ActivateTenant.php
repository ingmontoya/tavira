<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class ActivateTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:activate {tenant_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate an approved tenant (creates database, runs migrations and seeds)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");

            return 1;
        }

        $data = $tenant->data;
        $currentStatus = $data['status'] ?? 'unknown';
        $approvalStatus = $data['approval_status'] ?? 'unknown';

        if ($approvalStatus !== 'approved') {
            $this->error("Tenant is not approved. Approval status: {$approvalStatus}");
            $this->line("Use 'php artisan tenant:approve {$tenantId}' first to approve the tenant.");

            return 1;
        }

        if ($currentStatus === 'active') {
            $this->warn("Tenant {$tenantId} is already active.");

            return 0;
        }

        $this->info("Activating tenant: {$data['name']}");
        $this->line('Creating database and running migrations...');

        try {
            // Create tenant database
            $this->call('tenants:migrate', ['--tenant' => $tenantId]);
            $this->line('✓ Database migrations completed');

            // Seed tenant database
            $this->call('tenants:seed', ['--tenant' => $tenantId]);
            $this->line('✓ Database seeding completed');

            // Update tenant status
            $data['status'] = 'active';
            $data['activated_at'] = now()->toISOString();
            $data['activated_by'] = 'console';

            $tenant->update(['data' => $data]);

            $this->info("✓ Tenant {$tenantId} has been successfully activated!");

            // Display tenant information
            $this->table(
                ['Field', 'Value'],
                [
                    ['Name', $data['name'] ?? 'N/A'],
                    ['Email', $data['email'] ?? 'N/A'],
                    ['Status', $data['status']],
                    ['Approval Status', $data['approval_status']],
                    ['Domain', $tenant->domains->first()->domain ?? 'N/A'],
                    ['Activated At', $data['activated_at'] ?? 'N/A'],
                ]
            );

        } catch (\Exception $e) {
            $this->error('Failed to activate tenant: '.$e->getMessage());

            // Revert status if activation failed
            $data['status'] = 'approved';
            $tenant->update(['data' => $data]);

            return 1;
        }

        return 0;
    }
}
