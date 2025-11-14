<?php

namespace App\Console\Commands;

use App\Models\AccountingTransaction;
use App\Models\Expense;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDuplicateExpensePaymentTransactions extends Command
{
    protected $signature = 'expenses:fix-duplicate-payment-transactions {expense_id?} {--dry-run}';

    protected $description = 'Remove duplicate payment transactions where debit and credit are to the same cash/bank account';

    public function handle()
    {
        $expenseId = $this->argument('expense_id');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        // Find all payment transactions
        $query = AccountingTransaction::where('reference_type', 'expense_payment')
            ->with(['entries.account', 'reference']);

        if ($expenseId) {
            $query->where('reference_id', $expenseId);
        }

        $paymentTransactions = $query->get();

        if ($paymentTransactions->isEmpty()) {
            $this->info('No expense payment transactions found.');

            return 0;
        }

        $this->info("Found {$paymentTransactions->count()} payment transactions to check");

        $deleted = 0;
        $skipped = 0;

        foreach ($paymentTransactions as $transaction) {
            $expense = $transaction->reference;

            if (! $expense || ! ($expense instanceof Expense)) {
                $this->warn("Transaction #{$transaction->transaction_number}: No expense found - skipping");
                $skipped++;

                continue;
            }

            $entries = $transaction->entries;

            if ($entries->count() !== 2) {
                $this->warn("Transaction #{$transaction->transaction_number} (Expense #{$expense->expense_number}): Expected 2 entries, found {$entries->count()} - skipping");
                $skipped++;

                continue;
            }

            // Get the debit and credit entries
            $debitEntry = $entries->where('debit_amount', '>', 0)->first();
            $creditEntry = $entries->where('credit_amount', '>', 0)->first();

            if (! $debitEntry || ! $creditEntry) {
                $this->warn("Transaction #{$transaction->transaction_number} (Expense #{$expense->expense_number}): Missing debit or credit entry - skipping");
                $skipped++;

                continue;
            }

            // Check if both entries are for the same account (the bug we're fixing)
            if ($debitEntry->account_id === $creditEntry->account_id) {
                $accountCode = $debitEntry->account->code;
                $amount = $debitEntry->debit_amount;

                // Also check if it's a cash/bank account (1110xx or 1105xx)
                if (str_starts_with($accountCode, '1110') || str_starts_with($accountCode, '1105')) {
                    $this->error("Transaction #{$transaction->transaction_number} (Expense #{$expense->expense_number}): DUPLICATE - Debit and Credit both to account {$accountCode} for \${$amount}");

                    if (! $dryRun) {
                        try {
                            DB::connection('tenant')->transaction(function () use ($transaction) {
                                // Delete the transaction entries first
                                $transaction->entries()->delete();

                                // Delete the transaction
                                $transaction->delete();
                            });

                            $this->info('  ✓ Deleted duplicate transaction!');
                            $deleted++;
                        } catch (\Exception $e) {
                            $this->error("  ✗ Failed to delete transaction: {$e->getMessage()}");
                            $skipped++;
                        }
                    } else {
                        $deleted++;
                    }
                } else {
                    $this->warn("Transaction #{$transaction->transaction_number} (Expense #{$expense->expense_number}): Same account {$accountCode} but not cash/bank - skipping");
                    $skipped++;
                }
            } else {
                // This is a valid payment transaction (different accounts)
                $this->info("Transaction #{$transaction->transaction_number} (Expense #{$expense->expense_number}): OK - Debit {$debitEntry->account->code}, Credit {$creditEntry->account->code}");
                $skipped++;
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("  - Deleted: {$deleted}");
        $this->info("  - Skipped/OK: {$skipped}");
        $this->info("  - Total checked: {$paymentTransactions->count()}");

        if ($dryRun && $deleted > 0) {
            $this->newLine();
            $this->warn('This was a dry run. Run without --dry-run to delete duplicate transactions.');
        }

        return 0;
    }
}
