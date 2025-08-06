<?php

namespace App\Console\Commands;

use App\Models\AccountingTransaction;
use App\Models\AccountingTransactionEntry;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TruncateFinancialData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'financial:truncate {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate all financial data (invoices, payments, transactions, and account entries)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$this->option('force')) {
            if (!$this->confirm('This will permanently delete ALL financial data. Are you sure?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        $this->info('Starting truncation of financial data...');

        try {
            DB::transaction(function () {
                $this->info('Truncating accounting transaction entries...');
                AccountingTransactionEntry::truncate();

                $this->info('Truncating accounting transactions...');
                AccountingTransaction::truncate();

                $this->info('Truncating payments...');
                Payment::truncate();

                $this->info('Truncating invoices...');
                Invoice::truncate();
            });

            $this->info('✅ Financial data truncated successfully!');
            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error truncating financial data: ' . $e->getMessage());
            return 1;
        }
    }
}
