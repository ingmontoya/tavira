<?php

namespace App\Console\Commands;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\Tenant;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Console\Command;

class SyncChartOfAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chart-of-accounts:sync
                            {--tenant= : Sync for specific tenant ID}
                            {--all : Sync for all tenants}
                            {--force : Force sync even if accounts exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza el plan de cuentas contables para conjuntos que no tengan cuentas creadas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('all')) {
            $this->syncAllTenants();
        } elseif ($tenantId = $this->option('tenant')) {
            $this->syncTenant($tenantId);
        } else {
            $this->error('Debes especificar --all o --tenant=<id>');
            $this->info('Ejemplos:');
            $this->info('  php artisan chart-of-accounts:sync --all');
            $this->info('  php artisan chart-of-accounts:sync --tenant=abc123');
            $this->info('  php artisan chart-of-accounts:sync --all --force');

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    protected function syncAllTenants(): void
    {
        $tenants = Tenant::all();
        $this->info("Procesando {$tenants->count()} tenants...\n");

        foreach ($tenants as $tenant) {
            $this->syncTenant($tenant->id);
        }

        $this->newLine();
        $this->info('✓ Sincronización completada para todos los tenants');
    }

    protected function syncTenant(string $tenantId): void
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant {$tenantId} no encontrado");

            return;
        }

        $this->info("Procesando tenant: {$tenant->id}");

        tenancy()->initialize($tenant);

        $conjuntos = ConjuntoConfig::all();

        if ($conjuntos->isEmpty()) {
            $this->warn("  └─ Sin conjuntos configurados. Saltando...");
            tenancy()->end();

            return;
        }

        $synced = 0;
        $skipped = 0;

        foreach ($conjuntos as $conjunto) {
            $existingAccounts = ChartOfAccounts::where('conjunto_config_id', $conjunto->id)->count();

            if ($existingAccounts > 0 && ! $this->option('force')) {
                $this->line("  └─ Conjunto '{$conjunto->name}' (ID: {$conjunto->id}): {$existingAccounts} cuentas existentes. Saltando...");
                $skipped++;
                continue;
            }

            if ($existingAccounts > 0 && $this->option('force')) {
                $this->warn("  └─ Conjunto '{$conjunto->name}' (ID: {$conjunto->id}): Actualizando {$existingAccounts} cuentas...");
            }

            $seeder = new ChartOfAccountsSeeder;
            $reflection = new \ReflectionClass($seeder);
            $method = $reflection->getMethod('seedAccountsForConjunto');
            $method->setAccessible(true);
            $method->invoke($seeder, $conjunto->id);

            $totalAccounts = ChartOfAccounts::where('conjunto_config_id', $conjunto->id)->count();
            $this->info("  └─ Conjunto '{$conjunto->name}' (ID: {$conjunto->id}): ✓ {$totalAccounts} cuentas sincronizadas");
            $synced++;
        }

        tenancy()->end();

        $this->line("  Total: {$synced} conjuntos sincronizados, {$skipped} saltados");
    }
}
