<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Console\Command;

class EnablePanicButtonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'panic-button:enable {tenant_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable panic button feature for a specific tenant or all tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->argument('tenant_id');

        if ($tenantId) {
            // Enable for specific tenant
            $this->enableForTenant($tenantId);
        } else {
            // Enable for all tenants
            $this->enableForAllTenants();
        }

        return Command::SUCCESS;
    }

    private function enableForTenant(string $tenantId): void
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant with ID '{$tenantId}' not found.");

            return;
        }

        $tenant->run(function () use ($tenant) {
            TenantFeature::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'feature' => 'panic_button',
                ],
                [
                    'enabled' => true,
                ]
            );
        });

        $this->info("Panic button feature enabled for tenant: {$tenantId}");
    }

    private function enableForAllTenants(): void
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return;
        }

        $this->info("Enabling panic button feature for {$tenants->count()} tenants...");

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        foreach ($tenants as $tenant) {
            try {
                $tenant->run(function () use ($tenant) {
                    TenantFeature::updateOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'feature' => 'panic_button',
                        ],
                        [
                            'enabled' => true,
                        ]
                    );
                });

                $bar->advance();
            } catch (\Exception $e) {
                $this->error("\nFailed to enable panic button for tenant {$tenant->id}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->info("\n\nPanic button feature enabled for all tenants successfully!");
    }
}
