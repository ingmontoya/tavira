<?php

namespace App\Console\Commands;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Console\Command;

class DiagnoseClosureProblem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'closure:diagnose';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose closure problems and show detailed account information';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== DIAGNÓSTICO DE CIERRES FISCALES ===');
        $this->newLine();

        // 1. Listar todos los conjuntos
        $this->info('1. CONJUNTOS CONFIGURADOS:');
        $conjuntos = ConjuntoConfig::all();

        if ($conjuntos->isEmpty()) {
            $this->error('   ✗ No hay conjuntos configurados');

            return 1;
        }

        foreach ($conjuntos as $conjunto) {
            $activeLabel = $conjunto->is_active ? '✓ ACTIVO' : '  Inactivo';
            $this->line("   {$activeLabel} - ID: {$conjunto->id} - {$conjunto->name}");
        }
        $this->newLine();

        // 2. Identificar el conjunto activo
        $this->info('2. CONJUNTO ACTIVO (usado para cierres):');
        $conjuntoActivo = ConjuntoConfig::where('is_active', true)->first();

        if (! $conjuntoActivo) {
            $this->error('   ✗ No hay conjunto activo configurado');

            return 1;
        }

        $this->line("   ID: {$conjuntoActivo->id}");
        $this->line("   Nombre: {$conjuntoActivo->name}");
        $this->newLine();

        // 3. Contar cuentas por conjunto
        $this->info('3. CUENTAS POR CONJUNTO:');
        foreach ($conjuntos as $conjunto) {
            $count = ChartOfAccounts::where('conjunto_config_id', $conjunto->id)->count();
            $activeLabel = $conjunto->is_active ? '(ACTIVO)' : '';
            $this->line("   Conjunto ID {$conjunto->id} {$activeLabel}: {$count} cuentas");

            if ($count > 0) {
                // Mostrar algunas cuentas de ejemplo
                $sample = ChartOfAccounts::where('conjunto_config_id', $conjunto->id)
                    ->limit(3)
                    ->get(['code', 'name']);
                foreach ($sample as $account) {
                    $this->line("     - {$account->code}: {$account->name}");
                }
            }
        }
        $this->newLine();

        // 4. Verificar cuentas críticas en el conjunto ACTIVO
        $this->info('4. CUENTAS CRÍTICAS EN CONJUNTO ACTIVO (ID: '.$conjuntoActivo->id.'):');
        $requiredAccounts = [
            '590505' => 'GANANCIAS Y PERDIDAS DEL EJERCICIO',
            '360505' => 'EXCEDENTE DEL EJERCICIO',
            '361005' => 'DEFICIT DEL EJERCICIO',
        ];

        $hasAllAccounts = true;
        foreach ($requiredAccounts as $code => $expectedName) {
            $account = ChartOfAccounts::where('conjunto_config_id', $conjuntoActivo->id)
                ->where('code', $code)
                ->first();

            if ($account) {
                $this->line("   ✓ {$code} - {$account->name}");
            } else {
                $this->error("   ✗ {$code} - {$expectedName} [FALTANTE]");
                $hasAllAccounts = false;
            }
        }
        $this->newLine();

        // 5. Buscar cuentas críticas en OTROS conjuntos
        if (! $hasAllAccounts) {
            $this->warn('5. BUSCANDO CUENTAS CRÍTICAS EN OTROS CONJUNTOS:');
            foreach ($requiredAccounts as $code => $expectedName) {
                $accountsInOtherConjuntos = ChartOfAccounts::where('code', $code)
                    ->where('conjunto_config_id', '!=', $conjuntoActivo->id)
                    ->get(['id', 'conjunto_config_id', 'name']);

                if ($accountsInOtherConjuntos->isNotEmpty()) {
                    $this->line("   Cuenta {$code} encontrada en otros conjuntos:");
                    foreach ($accountsInOtherConjuntos as $account) {
                        $conjunto = ConjuntoConfig::find($account->conjunto_config_id);
                        $this->line("     - Conjunto ID {$account->conjunto_config_id} ({$conjunto->name}): {$account->name}");
                    }
                } else {
                    $this->line("   Cuenta {$code} NO encontrada en ningún conjunto");
                }
            }
            $this->newLine();

            // 6. Solución
            $this->info('6. SOLUCIÓN:');
            $this->warn('   Las cuentas están en conjuntos diferentes. Ejecute:');
            $this->line('   php artisan tenants:seed --class=ChartOfAccountsSeeder');
            $this->line('   Esto creará las cuentas faltantes para el conjunto activo.');
        } else {
            $this->info('5. RESULTADO:');
            $this->line('   ✓ Todas las cuentas necesarias están presentes');
            $this->line('   ✓ El sistema debería funcionar correctamente');
            $this->newLine();
            $this->warn('   Si sigue obteniendo errores, es posible que haya un problema de caché.');
            $this->line('   Intente ejecutar: php artisan cache:clear');
        }

        return 0;
    }
}
