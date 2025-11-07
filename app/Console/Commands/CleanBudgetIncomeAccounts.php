<?php

namespace App\Console\Commands;

use App\Models\Budget;
use App\Models\BudgetItem;
use Illuminate\Console\Command;

class CleanBudgetIncomeAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'budgets:clean-income-accounts {--budget-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Remove income account items from draft budgets';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('budget-id')) {
            $budget = Budget::find($this->option('budget-id'));

            if (! $budget) {
                $this->error('Budget not found');

                return self::FAILURE;
            }

            $budgets = collect([$budget]);
        } else {
            // Get all draft budgets
            $budgets = Budget::where('status', 'draft')->get();
        }

        if ($budgets->isEmpty()) {
            $this->info('No draft budgets found');

            return self::SUCCESS;
        }

        $this->info("Found {$budgets->count()} draft budget(s) to process");

        $totalRemoved = 0;

        foreach ($budgets as $budget) {
            $this->line("\nProcessing: {$budget->name} (ID: {$budget->id})");

            // Get income items
            $incomeItems = $budget->items()->where('category', 'income')->get();

            if ($incomeItems->isEmpty()) {
                $this->comment('  No income accounts found - skipping');
                continue;
            }

            $this->warn("  Found {$incomeItems->count()} income account(s):");

            foreach ($incomeItems as $item) {
                $this->line("    - {$item->account->code} - {$item->account->name} (\${$item->budgeted_amount})");
            }

            if ($this->confirm('  Remove these income accounts?', true)) {
                foreach ($incomeItems as $item) {
                    $item->delete();
                    $totalRemoved++;
                }

                // Recalculate totals
                $budget->calculateTotals();

                $this->info("  ✓ Removed {$incomeItems->count()} income account(s)");
            } else {
                $this->comment('  Skipped');
            }
        }

        $this->newLine();
        $this->info("✓ Process completed. Total accounts removed: {$totalRemoved}");

        return self::SUCCESS;
    }
}
