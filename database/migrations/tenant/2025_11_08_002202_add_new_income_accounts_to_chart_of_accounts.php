<?php

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get all conjunto configs
        $conjuntos = ConjuntoConfig::all();

        foreach ($conjuntos as $conjunto) {
            // Primero agregar la cuenta padre 4135 si no existe
            $parent4135 = ChartOfAccounts::forConjunto($conjunto->id)
                ->where('code', '4135')
                ->first();

            if (! $parent4135) {
                ChartOfAccounts::create([
                    'conjunto_config_id' => $conjunto->id,
                    'code' => '4135',
                    'name' => 'ACTIVIDADES DE PROPIEDAD HORIZONTAL',
                    'description' => 'ACTIVIDADES DE PROPIEDAD HORIZONTAL',
                    'account_type' => 'income',
                    'parent_code' => '41',
                    'level' => 3,
                    'is_active' => true,
                    'requires_third_party' => false,
                    'nature' => 'credit',
                    'accepts_posting' => false,
                ]);
            }

            // Agregar las nuevas cuentas de ingreso
            $newAccounts = [
                [
                    'code' => '413501',
                    'name' => 'CUOTAS ORDINARIAS DE ADMINISTRACIÓN',
                    'description' => 'CUOTAS ORDINARIAS DE ADMINISTRACIÓN',
                ],
                [
                    'code' => '413502',
                    'name' => 'CUOTAS EXTRAORDINARIAS',
                    'description' => 'CUOTAS EXTRAORDINARIAS',
                ],
                [
                    'code' => '413503',
                    'name' => 'PARQUEADEROS',
                    'description' => 'PARQUEADEROS',
                ],
                [
                    'code' => '413505',
                    'name' => 'MULTAS Y SANCIONES',
                    'description' => 'MULTAS Y SANCIONES',
                ],
                [
                    'code' => '413506',
                    'name' => 'INTERESES DE MORA',
                    'description' => 'INTERESES DE MORA',
                ],
            ];

            foreach ($newAccounts as $accountData) {
                // Check if account already exists
                $exists = ChartOfAccounts::forConjunto($conjunto->id)
                    ->where('code', $accountData['code'])
                    ->exists();

                if (! $exists) {
                    ChartOfAccounts::create([
                        'conjunto_config_id' => $conjunto->id,
                        'code' => $accountData['code'],
                        'name' => $accountData['name'],
                        'description' => $accountData['description'],
                        'account_type' => 'income',
                        'parent_code' => '4135',
                        'level' => 4,
                        'is_active' => true,
                        'requires_third_party' => false,
                        'nature' => 'credit',
                        'accepts_posting' => true,
                    ]);
                }
            }

            // Actualizar la descripción de 417005 para aclarar su uso
            $account417005 = ChartOfAccounts::forConjunto($conjunto->id)
                ->where('code', '417005')
                ->first();

            if ($account417005) {
                $account417005->update([
                    'name' => 'CUOTAS DE ADMINISTRACIÓN (Antiguos Anticipos)',
                    'description' => 'CUOTAS DE ADMINISTRACIÓN - Cuenta legacy para anticipos, usar 413501 para nuevas transacciones',
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $conjuntos = ConjuntoConfig::all();

        foreach ($conjuntos as $conjunto) {
            // Remove the new accounts
            ChartOfAccounts::forConjunto($conjunto->id)
                ->whereIn('code', ['413501', '413502', '413503', '413505', '413506', '4135'])
                ->delete();

            // Restore 417005 original name
            $account417005 = ChartOfAccounts::forConjunto($conjunto->id)
                ->where('code', '417005')
                ->first();

            if ($account417005) {
                $account417005->update([
                    'name' => 'CUOTAS DE ADMINISTRACIÓN',
                    'description' => 'CUOTAS DE ADMINISTRACIÓN',
                ]);
            }
        }
    }
};
