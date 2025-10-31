<?php

namespace App\Console\Commands;

use App\Models\Expense;
use Illuminate\Console\Command;

class FixExpenseAccountingTransactions extends Command
{
    protected $signature = 'expenses:fix-accounting-transactions {expense_id?} {--dry-run}';

    protected $description = 'Fix accounting transactions for expenses with withholding tax that were created incorrectly';

    public function handle()
    {
        $expenseId = $this->argument('expense_id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $query = Expense::whereNotNull('tax_account_id')
            ->where('tax_amount', '>', 0)
            ->whereIn('status', ['aprobado', 'pagado']);

        if ($expenseId) {
            $query->where('id', $expenseId);
        }

        $expenses = $query->with(['accountingTransactions.entries.account'])->get();

        if ($expenses->isEmpty()) {
            $this->info('No expenses found with withholding tax that need fixing.');

            return 0;
        }

        $this->info("Found {$expenses->count()} expenses with withholding tax to check");

        $fixed = 0;
        $skipped = 0;

        foreach ($expenses as $expense) {
            // Get the provision/causation transaction (not the payment transaction)
            $provisionTransaction = $expense->accountingTransactions()
                ->where('reference_type', 'expense')
                ->first();

            if (! $provisionTransaction) {
                $this->warn("Expense #{$expense->id} ({$expense->expense_number}): No provision transaction found - skipping");
                $skipped++;

                continue;
            }

            // Check if the credit to account 2335 (or credit_account_id) is incorrect
            $creditEntry = $provisionTransaction->entries()
                ->where('account_id', $expense->credit_account_id)
                ->first();

            if (! $creditEntry) {
                $this->warn("Expense #{$expense->id} ({$expense->expense_number}): No credit entry found for account {$expense->credit_account_id} - skipping");
                $skipped++;

                continue;
            }

            $expectedNetAmount = $expense->total_amount - $expense->tax_amount;
            $currentCreditAmount = $creditEntry->credit_amount;

            // Check if it's using total_amount instead of net amount
            if (abs($currentCreditAmount - $expense->total_amount) < 0.01) {
                $this->error("Expense #{$expense->id} ({$expense->expense_number}): INCORRECT - Credit to {$creditEntry->account->code} is {$currentCreditAmount}, should be {$expectedNetAmount}");

                if (! $dryRun) {
                    // Fix the entry
                    $creditEntry->update(['credit_amount' => $expectedNetAmount]);

                    // Recalculate transaction totals
                    $provisionTransaction->calculateTotals();

                    $this->info('  ✓ Fixed!');
                }

                $fixed++;
            } elseif (abs($currentCreditAmount - $expectedNetAmount) < 0.01) {
                $this->info("Expense #{$expense->id} ({$expense->expense_number}): OK - Credit amount is correct ({$expectedNetAmount})");
                $skipped++;
            } else {
                $this->warn("Expense #{$expense->id} ({$expense->expense_number}): UNEXPECTED - Credit amount is {$currentCreditAmount}, expected {$expectedNetAmount}");
                $skipped++;
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("  - Fixed: {$fixed}");
        $this->info("  - Skipped/OK: {$skipped}");
        $this->info("  - Total checked: {$expenses->count()}");

        if ($dryRun && $fixed > 0) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to apply fixes.');
        }

        return 0;
    }
}
