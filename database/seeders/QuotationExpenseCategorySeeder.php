<?php

namespace Database\Seeders;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class QuotationExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        if (! $conjunto) {
            $this->command->warn('No active conjunto found. Skipping QuotationExpenseCategorySeeder.');

            return;
        }

        // Find appropriate accounts for contracted services
        // 5135 - Servicios (Expense account)
        $debitAccount = ChartOfAccounts::forConjunto($conjunto->id)
            ->where('code', 'LIKE', '5135%')
            ->postable()
            ->first();

        // 2335 - Costos y gastos por pagar (Liability account)
        $creditAccount = ChartOfAccounts::forConjunto($conjunto->id)
            ->where('code', 'LIKE', '2335%')
            ->postable()
            ->first();

        if (! $debitAccount || ! $creditAccount) {
            $this->command->warn('Required accounts not found. Skipping category creation.');

            return;
        }

        // Create or update the category
        ExpenseCategory::updateOrCreate(
            [
                'conjunto_config_id' => $conjunto->id,
                'name' => 'Servicios Contratados',
            ],
            [
                'description' => 'Servicios contratados mediante solicitudes de cotización a proveedores',
                'is_active' => true,
                'requires_approval' => true,
                'default_debit_account_id' => $debitAccount->id,
                'default_credit_account_id' => $creditAccount->id,
            ]
        );

        $this->command->info('Quotation expense category created successfully.');
    }
}
