<?php

namespace App\Console\Commands;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Console\Command;

class AddMissingClosureAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'closure:add-missing-accounts {--conjunto-id= : Specific conjunto ID to fix}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add missing closure accounts (590505, 360505, 361005) to conjunto';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Agregando cuentas de cierre faltantes...');
        $this->newLine();

        $conjuntoId = $this->option('conjunto-id');

        if ($conjuntoId) {
            $conjunto = ConjuntoConfig::find($conjuntoId);
            if (! $conjunto) {
                $this->error("Conjunto con ID {$conjuntoId} no encontrado");

                return 1;
            }
            $this->addAccountsToConjunto($conjunto);
        } else {
            // Procesar el conjunto activo
            $conjunto = ConjuntoConfig::where('is_active', true)->first();
            if (! $conjunto) {
                $this->error('No hay conjunto activo');

                return 1;
            }
            $this->addAccountsToConjunto($conjunto);
        }

        $this->newLine();
        $this->info('✓ Proceso completado');

        return 0;
    }

    private function addAccountsToConjunto(ConjuntoConfig $conjunto)
    {
        $this->line("Procesando conjunto: {$conjunto->name} (ID: {$conjunto->id})");

        // Definir las cuentas necesarias con su jerarquía completa
        $accounts = $this->getClosureAccountsHierarchy();

        $added = 0;
        $existing = 0;

        foreach ($accounts as $accountData) {
            // Verificar si ya existe
            $exists = ChartOfAccounts::where('conjunto_config_id', $conjunto->id)
                ->where('code', $accountData['code'])
                ->exists();

            if ($exists) {
                $this->line("  • {$accountData['code']} - {$accountData['name']} [Ya existe]");
                $existing++;

                continue;
            }

            // Buscar la cuenta padre si existe
            $parentId = null;
            if ($accountData['parent_code']) {
                $parent = ChartOfAccounts::where('conjunto_config_id', $conjunto->id)
                    ->where('code', $accountData['parent_code'])
                    ->first();
                $parentId = $parent?->id;
            }

            // Crear la cuenta
            ChartOfAccounts::create([
                'conjunto_config_id' => $conjunto->id,
                'code' => $accountData['code'],
                'name' => $accountData['name'],
                'description' => $accountData['description'],
                'account_type' => $accountData['account_type'],
                'parent_id' => $parentId,
                'level' => $accountData['level'],
                'is_active' => true,
                'requires_third_party' => false,
                'nature' => $accountData['nature'],
                'accepts_posting' => $accountData['accepts_posting'],
            ]);

            $this->info("  ✓ {$accountData['code']} - {$accountData['name']} [Creada]");
            $added++;
        }

        $this->newLine();
        $this->line('Resumen:');
        $this->line("  Cuentas creadas: {$added}");
        $this->line("  Cuentas existentes: {$existing}");
    }

    private function getClosureAccountsHierarchy(): array
    {
        return [
            // Clase 5 - GASTOS (si no existe)
            [
                'code' => '5',
                'name' => 'GASTOS',
                'description' => 'GASTOS',
                'account_type' => 'expense',
                'parent_code' => null,
                'level' => 1,
                'nature' => 'debit',
                'accepts_posting' => false,
            ],

            // Grupo 59 - GANANCIAS Y PERDIDAS
            [
                'code' => '59',
                'name' => 'GANANCIAS Y PERDIDAS',
                'description' => 'GANANCIAS Y PERDIDAS',
                'account_type' => 'expense',
                'parent_code' => '5',
                'level' => 2,
                'nature' => 'debit',
                'accepts_posting' => false,
            ],

            // Cuenta 5905 - GANANCIAS Y PERDIDAS
            [
                'code' => '5905',
                'name' => 'GANANCIAS Y PERDIDAS',
                'description' => 'GANANCIAS Y PERDIDAS',
                'account_type' => 'expense',
                'parent_code' => '59',
                'level' => 3,
                'nature' => 'debit',
                'accepts_posting' => false,
            ],

            // Subcuenta 590505 - GANANCIAS Y PERDIDAS DEL EJERCICIO
            [
                'code' => '590505',
                'name' => 'GANANCIAS Y PERDIDAS DEL EJERCICIO',
                'description' => 'GANANCIAS Y PERDIDAS DEL EJERCICIO',
                'account_type' => 'expense',
                'parent_code' => '5905',
                'level' => 4,
                'nature' => 'debit',
                'accepts_posting' => true,
            ],

            // Clase 3 - PATRIMONIO (si no existe)
            [
                'code' => '3',
                'name' => 'PATRIMONIO',
                'description' => 'PATRIMONIO',
                'account_type' => 'equity',
                'parent_code' => null,
                'level' => 1,
                'nature' => 'credit',
                'accepts_posting' => false,
            ],

            // Grupo 36 - RESULTADOS DEL EJERCICIO
            [
                'code' => '36',
                'name' => 'RESULTADOS DEL EJERCICIO',
                'description' => 'RESULTADOS DEL EJERCICIO',
                'account_type' => 'equity',
                'parent_code' => '3',
                'level' => 2,
                'nature' => 'credit',
                'accepts_posting' => false,
            ],

            // Cuenta 3605 - EXCEDENTE DEL EJERCICIO
            [
                'code' => '3605',
                'name' => 'EXCEDENTE DEL EJERCICIO',
                'description' => 'EXCEDENTE DEL EJERCICIO',
                'account_type' => 'equity',
                'parent_code' => '36',
                'level' => 3,
                'nature' => 'credit',
                'accepts_posting' => false,
            ],

            // Subcuenta 360505 - EXCEDENTE DEL EJERCICIO
            [
                'code' => '360505',
                'name' => 'EXCEDENTE DEL EJERCICIO',
                'description' => 'EXCEDENTE DEL EJERCICIO',
                'account_type' => 'equity',
                'parent_code' => '3605',
                'level' => 4,
                'nature' => 'credit',
                'accepts_posting' => true,
            ],

            // Cuenta 3610 - DEFICIT DEL EJERCICIO
            [
                'code' => '3610',
                'name' => 'DEFICIT DEL EJERCICIO',
                'description' => 'DEFICIT DEL EJERCICIO',
                'account_type' => 'equity',
                'parent_code' => '36',
                'level' => 3,
                'nature' => 'credit',
                'accepts_posting' => false,
            ],

            // Subcuenta 361005 - DEFICIT DEL EJERCICIO
            [
                'code' => '361005',
                'name' => 'DEFICIT DEL EJERCICIO',
                'description' => 'DEFICIT DEL EJERCICIO',
                'account_type' => 'equity',
                'parent_code' => '3610',
                'level' => 4,
                'nature' => 'credit',
                'accepts_posting' => true,
            ],
        ];
    }
}
