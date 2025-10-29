<?php

namespace Database\Seeders;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates default expense categories for the conjunto with proper accounting mappings
     * based on Colombian chart of accounts (Decreto 2650).
     */
    public function run(): void
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        if (! $conjunto) {
            $this->command->warn('No active conjunto found. Skipping ExpenseCategorySeeder.');
            return;
        }

        // Define expense categories with their Colombian accounting codes
        $categories = [
            [
                'name' => 'Servicios Públicos',
                'description' => 'Gastos en energía eléctrica, agua, gas, telefonía e internet',
                'color' => '#3B82F6',
                'icon' => 'bolt',
                'requires_approval' => false,
                'debit_code' => '5135', // 5135 - Servicios
                'credit_code' => '1110', // 1110 - Bancos (efectivo)
                'tax_code' => '236525', // 236525 - SERVICIOS (Retención en la fuente)
            ],
            [
                'name' => 'Personal',
                'description' => 'Sueldos, salarios, prestaciones sociales y beneficios del personal',
                'color' => '#10B981',
                'icon' => 'users',
                'requires_approval' => true,
                'debit_code' => '510506', // 510506 - SUELDOS
                'credit_code' => '1110', // 1110 - BANCOS (pago directo de nómina)
                'tax_code' => '236505', // 236505 - SALARIOS Y PAGOS LABORALES (Retención en la fuente)
            ],
            [
                'name' => 'Mantenimiento',
                'description' => 'Reparaciones y mantenimiento preventivo/correctivo de instalaciones',
                'color' => '#F59E0B',
                'icon' => 'wrench-screwdriver',
                'requires_approval' => true,
                'debit_code' => '5160', // 5160 - Adecuación e instalación
                'credit_code' => '2335', // 2335 - Costos y gastos por pagar
                'tax_code' => '236525', // 236525 - SERVICIOS (Retención en la fuente)
            ],
            [
                'name' => 'Vigilancia',
                'description' => 'Servicios de seguridad, vigilancia y monitoreo',
                'color' => '#EF4444',
                'icon' => 'shield-check',
                'requires_approval' => false,
                'debit_code' => '5135', // 5135 - Servicios
                'credit_code' => '2335', // 2335 - Costos y gastos por pagar
                'tax_code' => '236525', // 236525 - SERVICIOS (Retención en la fuente)
            ],
            [
                'name' => 'Limpieza',
                'description' => 'Servicios de aseo, limpieza e insumos de limpieza',
                'color' => '#8B5CF6',
                'icon' => 'sparkles',
                'requires_approval' => false,
                'debit_code' => '5135', // 5135 - Servicios
                'credit_code' => '1110', // 1110 - Bancos
                'tax_code' => '236525', // 236525 - SERVICIOS (Retención en la fuente)
            ],
            [
                'name' => 'Jardinería',
                'description' => 'Mantenimiento de zonas verdes, jardines y plantas ornamentales',
                'color' => '#22C55E',
                'icon' => 'leaf',
                'requires_approval' => false,
                'debit_code' => '5135', // 5135 - Servicios
                'credit_code' => '1110', // 1110 - Bancos
                'tax_code' => '236525', // 236525 - SERVICIOS (Retención en la fuente)
            ],
            [
                'name' => 'Administrativos',
                'description' => 'Gastos administrativos generales, papelería y suministros de oficina',
                'color' => '#6B7280',
                'icon' => 'document-text',
                'requires_approval' => true,
                'debit_code' => '5195', // 5195 - Diversos
                'credit_code' => '1110', // 1110 - Bancos
                'tax_code' => '236525', // 236525 - SERVICIOS (Retención en la fuente)
            ],
            [
                'name' => 'Seguros',
                'description' => 'Pólizas de seguros de edificación, responsabilidad civil y otros',
                'color' => '#06B6D4',
                'icon' => 'shield-exclamation',
                'requires_approval' => true,
                'debit_code' => '5110', // 5110 - Seguros
                'credit_code' => '1110', // 1110 - Bancos
                'tax_code' => '236525', // 236525 - SERVICIOS (Retención en la fuente)
            ],
            [
                'name' => 'Legales y Contables',
                'description' => 'Honorarios profesionales de contadores, abogados y consultores',
                'color' => '#8B5CF6',
                'icon' => 'scale',
                'requires_approval' => true,
                'debit_code' => '511015', // 511015 - CONTABILIDAD
                'credit_code' => '23352510', // 23352510 - CONTADOR
                'tax_code' => '236515', // 236515 - HONORARIOS (Retención en la fuente)
            ],
            [
                'name' => 'Servicios Contratados',
                'description' => 'Servicios contratados mediante solicitudes de cotización a proveedores',
                'color' => '#F97316',
                'icon' => 'clipboard-document-check',
                'requires_approval' => true,
                'debit_code' => '5135', // 5135 - Servicios
                'credit_code' => '2335', // 2335 - Costos y gastos por pagar
                'tax_code' => '236525', // 236525 - SERVICIOS (Retención en la fuente)
            ],
            [
                'name' => 'Otros Gastos',
                'description' => 'Gastos diversos no clasificados en otras categorías',
                'color' => '#9CA3AF',
                'icon' => 'ellipsis-horizontal',
                'requires_approval' => true,
                'debit_code' => '5195', // 5195 - Diversos
                'credit_code' => '1110', // 1110 - Bancos
                'tax_code' => '236525', // 236525 - SERVICIOS (Retención en la fuente)
            ],
        ];

        $this->command->info('Creating expense categories with accounting mappings...');

        foreach ($categories as $categoryData) {
            // Find the debit account (expense account)
            $debitAccount = ChartOfAccounts::forConjunto($conjunto->id)
                ->where('code', 'LIKE', $categoryData['debit_code'] . '%')
                ->postable()
                ->first();

            // Find the credit account (liability or asset account)
            $creditAccount = ChartOfAccounts::forConjunto($conjunto->id)
                ->where('code', 'LIKE', $categoryData['credit_code'] . '%')
                ->postable()
                ->first();

            // Find the tax/withholding account (retención en la fuente)
            $taxAccount = null;
            if (isset($categoryData['tax_code'])) {
                $taxAccount = ChartOfAccounts::forConjunto($conjunto->id)
                    ->where('code', 'LIKE', $categoryData['tax_code'] . '%')
                    ->postable()
                    ->first();
            }

            if (! $debitAccount) {
                $this->command->warn("Debit account {$categoryData['debit_code']} not found for category: {$categoryData['name']}");
                continue;
            }

            if (! $creditAccount) {
                $this->command->warn("Credit account {$categoryData['credit_code']} not found for category: {$categoryData['name']}");
                continue;
            }

            // Find budget account (same as debit account for expense categories)
            $budgetAccount = $debitAccount;

            $categoryAttributes = [
                'description' => $categoryData['description'],
                'is_active' => true,
                'color' => $categoryData['color'],
                'icon' => $categoryData['icon'],
                'requires_approval' => $categoryData['requires_approval'],
                'default_debit_account_id' => $debitAccount->id,
                'default_credit_account_id' => $creditAccount->id,
                'budget_account_id' => $budgetAccount->id,
            ];

            // Add tax account if found
            if ($taxAccount) {
                $categoryAttributes['default_tax_account_id'] = $taxAccount->id;
            }

            ExpenseCategory::updateOrCreate(
                [
                    'conjunto_config_id' => $conjunto->id,
                    'name' => $categoryData['name'],
                ],
                $categoryAttributes
            );

            $taxInfo = $taxAccount ? ", Tax: {$taxAccount->code}" : '';
            $this->command->info("✓ Created category: {$categoryData['name']} (Debit: {$debitAccount->code}, Credit: {$creditAccount->code}{$taxInfo})");
        }

        $this->command->info('Expense categories created successfully!');
    }
}
