<?php

namespace App\Listeners;

use App\Events\AccountingTransactionPosted;
use App\Models\BudgetExecution;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class UpdateBudgetExecutionFromTransaction implements ShouldQueue
{
    use InteractsWithQueue;

    public function handle(AccountingTransactionPosted $event): void
    {
        $transaction = $event->transaction;
        $transactionDate = $transaction->transaction_date;
        $month = $transactionDate->month;
        $year = $transactionDate->year;

        // Get all unique accounts involved in this transaction
        $accountIds = $transaction->entries()->pluck('account_id')->unique();

        // Update budget executions for each account in this transaction
        foreach ($accountIds as $accountId) {
            BudgetExecution::refreshExecutionsForAccount($accountId, $month, $year);
        }
    }
}