<?php

namespace Database\Seeders;

use App\Models\Budget;
use App\Models\BudgetExecution;
use App\Models\BudgetItem;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Database\Seeder;

class TestBudgetSeeder extends Seeder
{
    public function run(): void
    {
        // Get the active conjunto config
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        if (! $conjunto) {
            $this->command->error('No active conjunto configuration found');

            return;
        }

        $this->command->info("Creating test budget for conjunto: {$conjunto->name}");

        // Create a test budget for current year
        $budget = Budget::create([
            'conjunto_config_id' => $conjunto->id,
            'name' => 'Presupuesto Test '.date('Y'),
            'fiscal_year' => date('Y'),
            'start_date' => date('Y-01-01'),
            'end_date' => date('Y-12-31'),
            'status' => 'active',
            'total_budgeted_income' => 50000000,
            'total_budgeted_expenses' => 45000000,
        ]);

        $this->command->info("Budget created with ID: {$budget->id}");

        // Get accounts for budget items
        $incomeAccount = ChartOfAccounts::forConjunto($conjunto->id)
            ->where('account_type', 'income')
            ->first();

        $expenseAccount = ChartOfAccounts::forConjunto($conjunto->id)
            ->where('account_type', 'expense')
            ->first();

        if (! $incomeAccount || ! $expenseAccount) {
            // Create default accounts if they don't exist
            $this->command->info('Creating default income and expense accounts...');

            if (! $incomeAccount) {
                $incomeAccount = ChartOfAccounts::create([
                    'conjunto_config_id' => $conjunto->id,
                    'code' => '4110',
                    'name' => 'Ingresos por Administración',
                    'description' => 'Cuotas de administración mensuales',
                    'account_type' => 'income',
                    'nature' => 'credit',
                    'level' => 3,
                    'is_active' => true,
                    'accepts_posting' => true,
                ]);
            }

            if (! $expenseAccount) {
                $expenseAccount = ChartOfAccounts::create([
                    'conjunto_config_id' => $conjunto->id,
                    'code' => '5110',
                    'name' => 'Gastos de Administración',
                    'description' => 'Gastos operacionales del conjunto',
                    'account_type' => 'expense',
                    'nature' => 'debit',
                    'level' => 3,
                    'is_active' => true,
                    'accepts_posting' => true,
                ]);
            }
        }

        $this->command->info("Using accounts: {$incomeAccount->code} - {$incomeAccount->name}, {$expenseAccount->code} - {$expenseAccount->name}");

        // Create budget items
        $incomeItem = BudgetItem::create([
            'budget_id' => $budget->id,
            'account_id' => $incomeAccount->id,
            'category' => 'income',
            'budgeted_amount' => 50000000,
            'notes' => 'Ingresos por administración mensual',
        ]);

        $expenseItem = BudgetItem::create([
            'budget_id' => $budget->id,
            'account_id' => $expenseAccount->id,
            'category' => 'expense',
            'budgeted_amount' => 45000000,
            'notes' => 'Gastos operacionales mensuales',
        ]);

        $this->command->info('Budget items created');

        // Create budget executions for current month and previous months
        $currentMonth = date('n');
        $currentYear = date('Y');

        // Create executions for all months up to current month
        for ($month = 1; $month <= $currentMonth; $month++) {
            $monthlyIncomeBudget = 4166667; // 50M/12
            $monthlyExpenseBudget = 3750000; // 45M/12

            // Add some variance
            $actualIncome = $monthlyIncomeBudget + rand(-500000, 300000);
            $actualExpense = $monthlyExpenseBudget + rand(-200000, 600000);

            $incomeVariance = $actualIncome - $monthlyIncomeBudget;
            $expenseVariance = $actualExpense - $monthlyExpenseBudget;

            $incomeVariancePercentage = $monthlyIncomeBudget > 0 ? ($incomeVariance / $monthlyIncomeBudget) * 100 : 0;
            $expenseVariancePercentage = $monthlyExpenseBudget > 0 ? ($expenseVariance / $monthlyExpenseBudget) * 100 : 0;

            // Income execution
            BudgetExecution::create([
                'budget_item_id' => $incomeItem->id,
                'period_month' => $month,
                'period_year' => $currentYear,
                'budgeted_amount' => $monthlyIncomeBudget,
                'actual_amount' => $actualIncome,
                'variance_amount' => $incomeVariance,
                'variance_percentage' => round($incomeVariancePercentage, 2),
            ]);

            // Expense execution
            BudgetExecution::create([
                'budget_item_id' => $expenseItem->id,
                'period_month' => $month,
                'period_year' => $currentYear,
                'budgeted_amount' => $monthlyExpenseBudget,
                'actual_amount' => $actualExpense,
                'variance_amount' => $expenseVariance,
                'variance_percentage' => round($expenseVariancePercentage, 2),
            ]);
        }

        $this->command->info("Created budget executions for months 1-{$currentMonth} of {$currentYear}");
        $this->command->info('Test budget data created successfully!');
    }
}
