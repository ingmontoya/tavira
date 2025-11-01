<?php

namespace App\Console\Commands;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\Tenant;
use Illuminate\Console\Command;

class VerifyClosureAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'closure:verify-accounts {--fix : Create missing accounts automatically}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify that all required accounts for fiscal closures exist';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $fix = $this->option('fix');

        $requiredAccounts = [
            '590505' => 'GANANCIAS Y PERDIDAS DEL EJERCICIO',
            '360505' => 'EXCEDENTE DEL EJERCICIO',
            '361005' => 'DEFICIT DEL EJERCICIO',
        ];

        $this->info('Verificando cuentas necesarias para cierres fiscales...');
        $this->newLine();

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant, $requiredAccounts, $fix) {
                $this->info("Tenant: {$tenant->id}");

                $conjuntos = ConjuntoConfig::all();

                if ($conjuntos->isEmpty()) {
                    $this->warn('  ⚠ No hay conjuntos configurados');
                    $this->newLine();

                    return;
                }

                foreach ($conjuntos as $conjunto) {
                    $this->info("  Conjunto: {$conjunto->name} (ID: {$conjunto->id})");

                    $totalCuentas = ChartOfAccounts::forConjunto($conjunto->id)->count();
                    $this->line("    Total de cuentas: {$totalCuentas}");

                    $allPresent = true;
                    $missingAccounts = [];

                    foreach ($requiredAccounts as $code => $name) {
                        $account = ChartOfAccounts::forConjunto($conjunto->id)
                            ->where('code', $code)
                            ->first();

                        if ($account) {
                            $this->line("    ✓ {$code} - {$account->name}");
                        } else {
                            $this->error("    ✗ {$code} - {$name} [FALTANTE]");
                            $allPresent = false;
                            $missingAccounts[$code] = $name;
                        }
                    }

                    if (! $allPresent) {
                        if ($fix) {
                            $this->warn('    Ejecutando seeder para crear cuentas faltantes...');
                            $this->call('db:seed', ['--class' => 'ChartOfAccountsSeeder']);
                            $this->info('    ✓ Cuentas creadas');
                        } else {
                            $this->newLine();
                            $this->warn('    Para crear las cuentas faltantes, ejecute:');
                            $this->line('    php artisan tenants:seed --class=ChartOfAccountsSeeder');
                            $this->line('    O ejecute este comando con --fix: php artisan closure:verify-accounts --fix');
                        }
                    }

                    $this->newLine();
                }
            });
        }

        $this->info('Verificación completada.');

        return 0;
    }
}
