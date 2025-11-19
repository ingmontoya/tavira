<?php

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration fixes the incorrect structure of Class 2 (PASIVO) accounts
     * and adds missing accounts from the PUC standard for Propiedad Horizontal.
     *
     * IMPORTANT: This migration preserves all existing transactions by migrating
     * references to the correct account codes.
     */
    public function up(): void
    {
        $conjuntos = ConjuntoConfig::all();

        foreach ($conjuntos as $conjunto) {
            DB::transaction(function () use ($conjunto) {
                $this->fixClass2Structure($conjunto->id);
                $this->addMissingAccounts($conjunto->id);
            });
        }
    }

    /**
     * Fix the Class 2 (PASIVO) structure by reorganizing misplaced accounts
     */
    private function fixClass2Structure(int $conjuntoConfigId): void
    {
        // Step 1: Add missing parent groups
        $this->addMissingParentGroups($conjuntoConfigId);

        // Step 2: Reorganize accounts with incorrect codes
        $this->reorganizeAccounts($conjuntoConfigId);
    }

    /**
     * Add missing parent groups (level 2)
     */
    private function addMissingParentGroups(int $conjuntoConfigId): void
    {
        $parentGroups = [
            ['code' => '24', 'name' => 'IMPUESTOS, GRAVAMENES Y TASAS', 'parent_code' => '2'],
            ['code' => '25', 'name' => 'OBLIGACIONES LABORALES', 'parent_code' => '2'],
            ['code' => '27', 'name' => 'DIFERIDOS', 'parent_code' => '2'],
            ['code' => '28', 'name' => 'OTROS PASIVOS', 'parent_code' => '2'],
        ];

        foreach ($parentGroups as $groupData) {
            $parent = ChartOfAccounts::where('conjunto_config_id', $conjuntoConfigId)
                ->where('code', $groupData['parent_code'])
                ->first();

            ChartOfAccounts::updateOrCreate(
                [
                    'conjunto_config_id' => $conjuntoConfigId,
                    'code' => $groupData['code'],
                ],
                [
                    'name' => $groupData['name'],
                    'description' => $groupData['name'],
                    'account_type' => 'liability',
                    'parent_id' => $parent?->id,
                    'level' => 2,
                    'is_active' => true,
                    'requires_third_party' => false,
                    'nature' => 'credit',
                    'accepts_posting' => false,
                ]
            );
        }
    }

    /**
     * Reorganize accounts with incorrect codes
     */
    private function reorganizeAccounts(int $conjuntoConfigId): void
    {
        // Mapping of incorrect codes to correct codes
        $accountMigrations = [
            // Current incorrect code => [correct code, correct name, correct parent]
            '2404' => ['code' => '2404', 'name' => 'DE RENTA Y COMPLEMENTARIOS', 'parent' => '24'],
            '2408' => ['code' => '2408', 'name' => 'IMPUESTO SOBRE LAS VENTAS POR PAGAR', 'parent' => '24'],
            '2505' => ['code' => '2505', 'name' => 'SALARIOS POR PAGAR', 'parent' => '25'],
            '2510' => ['code' => '2510', 'name' => 'CESANTIAS CONSOLIDADAS', 'parent' => '25'],
            '2515' => ['code' => '2515', 'name' => 'INTERESES SOBRE CESANTIAS', 'parent' => '25'],
            '2520' => ['code' => '2520', 'name' => 'PRIMA DE SERVICIOS', 'parent' => '25'],
            '2525' => ['code' => '2525', 'name' => 'VACACIONES CONSOLIDADAS', 'parent' => '25'],
            '2540' => ['code' => '2540', 'name' => 'INDEMNIZACIONES LABORALES', 'parent' => '25'],
            '2705' => ['code' => '2705', 'name' => 'INGRESOS RECIBIDOS POR ANTICIPADO', 'parent' => '27'],
            '270505' => ['code' => '270505', 'name' => 'CUOTAS DE ADMINISTRACIÓN', 'parent' => '2705'],
            '270510' => ['code' => '270510', 'name' => 'CUOTAS EXTRAORDINARIAS', 'parent' => '2705'],
            '270595' => ['code' => '270595', 'name' => 'OTROS', 'parent' => '2705'],
            '2810' => ['code' => '2810', 'name' => 'DEPOSITOS RECIBIDOS', 'parent' => '28'],
            '281005' => ['code' => '281005', 'name' => 'PARA OBRAS E INVERSIONES', 'parent' => '2810'],
            '28100505' => ['code' => '28100505', 'name' => 'PARA OBRA DE FACHADAS', 'parent' => '281005'],
            '281035' => ['code' => '281035', 'name' => 'FONDO DE IMPREVISTOS', 'parent' => '28'],
            '210505' => ['code' => '210505', 'name' => 'PRESTAMOS', 'parent' => '2105'],
        ];

        foreach ($accountMigrations as $currentCode => $newData) {
            $existingAccount = ChartOfAccounts::where('conjunto_config_id', $conjuntoConfigId)
                ->where('code', $currentCode)
                ->first();

            if ($existingAccount) {
                // Get correct parent
                $parent = ChartOfAccounts::where('conjunto_config_id', $conjuntoConfigId)
                    ->where('code', $newData['parent'])
                    ->first();

                // Calculate level
                $level = $this->calculateLevel($newData['code']);

                // Update the account with correct information
                $existingAccount->update([
                    'name' => $newData['name'],
                    'description' => $newData['name'],
                    'parent_id' => $parent?->id,
                    'level' => $level,
                    'accepts_posting' => in_array($level, [4, 5]),
                ]);
            } else {
                // Create the account if it doesn't exist
                $parent = ChartOfAccounts::where('conjunto_config_id', $conjuntoConfigId)
                    ->where('code', $newData['parent'])
                    ->first();

                $level = $this->calculateLevel($newData['code']);

                ChartOfAccounts::create([
                    'conjunto_config_id' => $conjuntoConfigId,
                    'code' => $newData['code'],
                    'name' => $newData['name'],
                    'description' => $newData['name'],
                    'account_type' => 'liability',
                    'parent_id' => $parent?->id,
                    'level' => $level,
                    'is_active' => true,
                    'requires_third_party' => false,
                    'nature' => 'credit',
                    'accepts_posting' => in_array($level, [4, 5]),
                ]);
            }
        }

        // Delete accounts with incorrect codes that were invented
        $accountsToDelete = ['2511', '2805', '281055', '28103505'];
        foreach ($accountsToDelete as $code) {
            $account = ChartOfAccounts::where('conjunto_config_id', $conjuntoConfigId)
                ->where('code', $code)
                ->first();

            if ($account) {
                // Check if account has transactions
                $hasTransactions = DB::table('accounting_transaction_entries')
                    ->where('account_id', $account->id)
                    ->exists();

                if (! $hasTransactions) {
                    $account->delete();
                } else {
                    // If has transactions, migrate them to appropriate account
                    $this->migrateTransactions($account, $conjuntoConfigId);
                }
            }
        }
    }

    /**
     * Migrate transactions from incorrect account to correct one
     */
    private function migrateTransactions(ChartOfAccounts $oldAccount, int $conjuntoConfigId): void
    {
        // Mapping of old codes to new codes
        $migrationMap = [
            '2511' => '2505', // SALARIOS POR PAGAR
            '2805' => '2705', // INGRESOS RECIBIDOS POR ANTICIPADO
            '281055' => '270510', // CUOTAS EXTRAORDINARIAS
            '28103505' => '28100505', // PARA OBRA DE FACHADAS
        ];

        if (! isset($migrationMap[$oldAccount->code])) {
            return;
        }

        $newCode = $migrationMap[$oldAccount->code];
        $newAccount = ChartOfAccounts::where('conjunto_config_id', $conjuntoConfigId)
            ->where('code', $newCode)
            ->first();

        if ($newAccount) {
            // Update all transaction entries
            DB::table('accounting_transaction_entries')
                ->where('account_id', $oldAccount->id)
                ->update(['account_id' => $newAccount->id]);

            // Update budget items if any
            DB::table('budget_items')
                ->where('chart_of_account_id', $oldAccount->id)
                ->update(['chart_of_account_id' => $newAccount->id]);

            // Now safe to delete
            $oldAccount->delete();
        }
    }

    /**
     * Add missing accounts from the PUC standard
     */
    private function addMissingAccounts(int $conjuntoConfigId): void
    {
        $missingAccounts = [
            // Activos - Subcuentas de bancos (nivel 5 - 8 dígitos)
            ['11100501', 'NOMBRE DEL BANCO NUMERO Y TIPO DE CUENTA', '111005', 5, 'asset', 'debit'],
            ['11200501', 'NOMBRE DEL BANCO NUMERO Y TIPO DE CUENTA', '112005', 5, 'asset', 'debit'],

            // Activos - Subcuentas de zonas comunes en deudores (nivel 6 - 10 dígitos)
            ['1305053005', 'SALON SOCIAL', '13050530', 6, 'asset', 'debit'],
            ['1305053010', 'PARQUEADEROS', '13050530', 6, 'asset', 'debit'],
            ['1305053015', 'PISCINAS', '13050530', 6, 'asset', 'debit'],
            ['1305053020', 'GIMNASIOS', '13050530', 6, 'asset', 'debit'],
            ['1305053025', 'ZONA BBQ', '13050530', 6, 'asset', 'debit'],
            ['1305053030', 'CANCHAS DEPORTIVAS', '13050530', 6, 'asset', 'debit'],

            // Activos - Deterioro de cartera
            ['139905', 'PROPIETARIOS Y/O RESIDENTES', '1399', 4, 'asset', 'debit'],

            // Pasivos - Servicios de mantenimiento detallados
            ['23353520', 'PISCINAS', '233535', 5, 'liability', 'credit'],
            ['23353525', 'PARQUEADEROS', '233535', 5, 'liability', 'credit'],
            ['23353530', 'PLANTA ELÉCTRICA (INCLUYE COMBUSTIBLES Y LUBRICANTES)', '233535', 5, 'liability', 'credit'],
            ['23353535', 'TANQUES DE AGUA POTABLE', '233535', 5, 'liability', 'credit'],
            ['23353540', 'CAJAS DE AGUAS NEGRAS', '233535', 5, 'liability', 'credit'],
            ['23353545', 'EQUIPO DE CÓMPUTO', '233535', 5, 'liability', 'credit'],
            ['23353550', 'EQUIPO DE OFICINA', '233535', 5, 'liability', 'credit'],
            ['23353555', 'CITOFONOS', '233535', 5, 'liability', 'credit'],
            ['23353560', 'CCTV', '233535', 5, 'liability', 'credit'],
            ['23353565', 'REPARACIONES LOCATIVAS', '233535', 5, 'liability', 'credit'],
            ['23353570', 'CERRAJERÍA Y SIMILARES', '233535', 5, 'liability', 'credit'],
            ['23353575', 'ELÉCTRICOS (REDES-BOMBILLOS)', '233535', 5, 'liability', 'credit'],
            ['23353580', 'EXTINTORES', '233535', 5, 'liability', 'credit'],
            ['23353585', 'FUMIGACIÓN Y ROEDORES', '233535', 5, 'liability', 'credit'],
            ['23353590', 'MANTENIMIENTO CUBIERTAS Y FACHADAS', '233535', 5, 'liability', 'credit'],

            // Ingresos - Subcuentas de zonas comunes (nivel 5 - 8 dígitos)
            ['41703010', 'PARQUEADEROS', '417030', 5, 'income', 'credit'],
            ['41703015', 'PISCINAS', '417030', 5, 'income', 'credit'],
            ['41703020', 'GIMNASIO', '417030', 5, 'income', 'credit'],
            ['41703025', 'ZONA BBQ', '417030', 5, 'income', 'credit'],
            ['41703030', 'CANCHAS DEPORTIVAS', '417030', 5, 'income', 'credit'],

            // Ingresos - Cuentas faltantes
            ['417060', 'REINTEGRO DE OTROS COSTOS Y GASTOS', '4170', 4, 'income', 'credit'],
            ['417090', 'INGRESOS DE EJERCICIOS ANTERIORES', '4170', 4, 'income', 'credit'],

            // Gastos - Servicios
            ['513535', 'TELEFONO', '5135', 4, 'expense', 'debit'],
            ['513540', 'TELEVISIÓN E INTERNET', '5135', 4, 'expense', 'debit'],
            ['513545', 'CORREO, PORTES Y TELEGRAMAS', '5135', 4, 'expense', 'debit'],

            // Gastos - Adecuación
            ['5150', 'ADECUACION E INSTALACION', '51', 3, 'expense', 'debit'],
        ];

        foreach ($missingAccounts as $accountData) {
            [$code, $name, $parentCode, $level, $type, $nature] = $accountData;

            // Check if account already exists
            $exists = ChartOfAccounts::where('conjunto_config_id', $conjuntoConfigId)
                ->where('code', $code)
                ->exists();

            if ($exists) {
                continue;
            }

            // Get parent
            $parent = ChartOfAccounts::where('conjunto_config_id', $conjuntoConfigId)
                ->where('code', $parentCode)
                ->first();

            // Determine if requires third party
            $requiresThirdParty = in_array(substr($code, 0, 2), ['13', '23']) && $level >= 3;

            ChartOfAccounts::create([
                'conjunto_config_id' => $conjuntoConfigId,
                'code' => $code,
                'name' => $name,
                'description' => $name,
                'account_type' => $type,
                'parent_id' => $parent?->id,
                'level' => $level,
                'is_active' => true,
                'requires_third_party' => $requiresThirdParty,
                'nature' => $nature,
                'accepts_posting' => in_array($level, [4, 5, 6]),
            ]);
        }
    }

    /**
     * Calculate account level based on code length
     */
    private function calculateLevel(string $code): int
    {
        $length = strlen($code);

        return match ($length) {
            1 => 1,
            2 => 2,
            4 => 3,
            6 => 4,
            8 => 5,
            10 => 6,
            default => 1,
        };
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration should not be reversed as it fixes data integrity
        // If needed, restore from backup
    }
};
