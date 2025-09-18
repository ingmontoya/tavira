<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Console\Command;

class CheckPanicButtonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'panic-button:check {tenant_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check panic button feature status for tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');

        if ($tenantId) {
            $this->checkForTenant($tenantId);
        } else {
            $this->checkForAllTenants();
        }

        return Command::SUCCESS;
    }

    private function checkForTenant(string $tenantId): void
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant with ID '{$tenantId}' not found.");

            return;
        }

        $tenant->run(function () use ($tenant) {
            $feature = TenantFeature::where('tenant_id', $tenant->id)
                ->where('feature', 'panic_button')
                ->first();

            if ($feature) {
                $status = $feature->enabled ? 'ENABLED' : 'DISABLED';
                $this->info("Tenant {$tenant->id}: panic_button is {$status}");
            } else {
                $this->warn("Tenant {$tenant->id}: panic_button feature not found");
            }
        });
    }

    private function checkForAllTenants(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return;
        }

        $this->info("Checking panic button feature for {$tenants->count()} tenants:");
        $this->line('');

        $headers = ['Tenant ID', 'Admin Name', 'Status'];
        $rows = [];

        foreach ($tenants as $tenant) {
            try {
                $status = 'NOT FOUND';

                $tenant->run(function () use ($tenant, &$status) {
                    $feature = TenantFeature::where('tenant_id', $tenant->id)
                        ->where('feature', 'panic_button')
                        ->first();

                    if ($feature) {
                        $status = $feature->enabled ? 'ENABLED' : 'DISABLED';
                    }
                });

                $rows[] = [
                    $tenant->id,
                    $tenant->admin_name ?? 'N/A',
                    $status,
                ];
            } catch (\Exception $e) {
                $rows[] = [
                    $tenant->id,
                    $tenant->admin_name ?? 'N/A',
                    'ERROR: '.$e->getMessage(),
                ];
            }
        }

        $this->table($headers, $rows);
    }
}
