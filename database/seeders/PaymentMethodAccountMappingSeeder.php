<?php

namespace Database\Seeders;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\PaymentMethodAccountMapping;
use Illuminate\Database\Seeder;

class PaymentMethodAccountMappingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        if (! $conjunto) {
            $this->command->error('No active conjunto configuration found. Please run ConjuntoSeeder first.');

            return;
        }

        $this->command->info("Creating payment method account mappings for conjunto: {$conjunto->name}");

        // Define payment method to account mappings using correct PUC codes
        $paymentMethods = [
            'cash' => '110505', // Caja General
            'bank_transfer' => '111005', // Bancos Moneda Nacional
            'check' => '111005', // Bancos Moneda Nacional
            'credit_card' => '111005', // Bancos Moneda Nacional
            'debit_card' => '111005', // Bancos Moneda Nacional
            'online' => '111005', // Bancos Moneda Nacional
            'pse' => '111005', // Bancos Moneda Nacional
            'other' => '111005', // Bancos Moneda Nacional (default)
        ];

        foreach ($paymentMethods as $method => $accountCode) {
            $account = ChartOfAccounts::forConjunto($conjunto->id)
                ->where('code', $accountCode)
                ->first();

            if ($account) {
                PaymentMethodAccountMapping::firstOrCreate([
                    'conjunto_config_id' => $conjunto->id,
                    'payment_method' => $method,
                ], [
                    'cash_account_id' => $account->id,
                    'is_active' => true,
                ]);

                $this->command->info("✓ Created mapping: {$method} -> {$account->name} ({$accountCode})");
            } else {
                $this->command->warn("✗ Account not found for code: {$accountCode} (payment method: {$method})");
            }
        }

        $this->command->info('Payment method account mappings created successfully!');
    }
}
