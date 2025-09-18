<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;

class ApproveTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenant:approve {tenant_id} {--reject : Reject the tenant instead of approving} {--reason= : Rejection reason}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Approve or reject a tenant for activation';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');
        $isRejecting = $this->option('reject');
        $rejectionReason = $this->option('reason');

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant with ID {$tenantId} not found.");

            return 1;
        }

        $data = $tenant->data;
        $currentStatus = $data['approval_status'] ?? 'unknown';

        if ($currentStatus !== 'pending') {
            $this->error("Tenant is not in pending status. Current status: {$currentStatus}");

            return 1;
        }

        if ($isRejecting) {
            // Reject tenant
            $data['approval_status'] = 'rejected';
            $data['status'] = 'rejected';
            $data['rejected_at'] = now()->toISOString();
            $data['rejected_by'] = 'console'; // Could be improved to track which admin

            if ($rejectionReason) {
                $data['rejection_reason'] = $rejectionReason;
            }

            $this->info("Tenant {$tenantId} has been rejected.");

            if ($rejectionReason) {
                $this->line("Reason: {$rejectionReason}");
            }
        } else {
            // Approve tenant
            $data['approval_status'] = 'approved';
            $data['status'] = 'approved'; // Ready for activation
            $data['approved_at'] = now()->toISOString();
            $data['approved_by'] = 'console'; // Could be improved to track which admin

            $this->info("Tenant {$tenantId} has been approved and is ready for activation.");
            $this->line("Use 'php artisan tenant:activate {$tenantId}' to activate the tenant.");
        }

        // Update tenant data
        $tenant->update(['data' => $data]);

        // Display tenant information
        $this->table(
            ['Field', 'Value'],
            [
                ['Name', $data['name'] ?? 'N/A'],
                ['Email', $data['email'] ?? 'N/A'],
                ['Status', $data['status'] ?? 'N/A'],
                ['Approval Status', $data['approval_status'] ?? 'N/A'],
                ['Created By', $data['created_by_email'] ?? 'N/A'],
            ]
        );

        return 0;
    }
}
