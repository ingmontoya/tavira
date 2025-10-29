<?php

namespace App\Jobs;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\PaymentMethodAccountMapping;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Contracts\Tenant;

class InitializeTenantAccounting implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tenant;

    /**
     * Create a new job instance.
     */
    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Initializing accounting for tenant: {$this->tenant->id}");

            // Initialize tenant context
            $this->tenant->run(function () {
                // Seed chart of accounts
                $this->seedChartOfAccounts();

                // Create default payment method mappings
                $this->createDefaultPaymentMethodMappings();

                Log::info("Successfully initialized accounting for tenant: {$this->tenant->id}");
            });
        } catch (\Exception $e) {
            Log::error("Failed to initialize accounting for tenant {$this->tenant->id}: {$e->getMessage()}", [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Seed chart of accounts for all conjunto configs in the tenant
     */
    private function seedChartOfAccounts(): void
    {
        $seeder = new ChartOfAccountsSeeder;
        $seeder->run();

        Log::info('Chart of accounts seeded for tenant');
    }

    /**
     * Create default payment method account mappings
     */
    private function createDefaultPaymentMethodMappings(): void
    {
        $conjuntoConfigs = ConjuntoConfig::all();

        foreach ($conjuntoConfigs as $conjuntoConfig) {
            // Get the accounts needed for payment method mappings
            $cashAccount = ChartOfAccounts::forConjunto($conjuntoConfig->id)
                ->where('code', '110505') // CAJA GENERAL
                ->first();

            $bankAccount = ChartOfAccounts::forConjunto($conjuntoConfig->id)
                ->where('code', '111005') // BANCOS - MONEDA NACIONAL
                ->first();

            if (! $cashAccount || ! $bankAccount) {
                Log::warning("Required accounts not found for conjunto {$conjuntoConfig->id}, skipping payment method mappings");
                continue;
            }

            // Define payment methods and their default accounts
            $paymentMethods = [
                'cash' => $cashAccount->id,
                'bank_transfer' => $bankAccount->id,
                'check' => $bankAccount->id,
                'credit_card' => $bankAccount->id,
                'debit_card' => $bankAccount->id,
                'online' => $bankAccount->id,
                'pse' => $bankAccount->id,
                'other' => $bankAccount->id,
            ];

            // Create mappings
            foreach ($paymentMethods as $method => $accountId) {
                PaymentMethodAccountMapping::updateOrCreate(
                    [
                        'conjunto_config_id' => $conjuntoConfig->id,
                        'payment_method' => $method,
                    ],
                    [
                        'cash_account_id' => $accountId,
                        'is_active' => true,
                    ]
                );
            }

            Log::info("Created default payment method mappings for conjunto {$conjuntoConfig->id}");
        }
    }
}
