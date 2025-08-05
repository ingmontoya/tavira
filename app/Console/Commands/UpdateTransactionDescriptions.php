<?php

namespace App\Console\Commands;

use App\Models\AccountingTransaction;
use Illuminate\Console\Command;

class UpdateTransactionDescriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transactions:update-descriptions {--dry-run : Show what would be updated without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing transaction descriptions to prioritize apartment numbers';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No changes will be made');
        }

        $transactions = AccountingTransaction::where(function($query) {
            $query->where('reference_type', 'invoice')
                  ->orWhere('reference_type', 'payment');
        })
        ->with('reference')
        ->get();

        $updated = 0;
        $total = $transactions->count();

        $this->info("Found {$total} transactions to potentially update");

        foreach ($transactions as $transaction) {
            if (!$transaction->reference) {
                continue;
            }

            $newDescription = null;
            
            if ($transaction->reference_type === 'invoice') {
                $invoice = $transaction->reference;
                if ($invoice && $invoice->apartment) {
                    // Check current description format
                    if (!str_starts_with($transaction->description, 'Apto ')) {
                        // Extract concept type from current description, avoiding the apartment part
                        $conceptMatch = '';
                        $description = $transaction->description;
                        
                        // Remove apartment reference if it exists at the end
                        $description = preg_replace('/ - Apto [A-Z0-9]+$/', '', $description);
                        
                        // Look for concept after the invoice number
                        if (preg_match('/Factura [A-Z0-9-]+ - (.+)/', $description, $matches)) {
                            $conceptMatch = ' - ' . trim($matches[1]);
                        }
                        
                        $newDescription = "Apto {$invoice->apartment->number} - Factura {$invoice->invoice_number}{$conceptMatch}";
                    }
                }
            } elseif ($transaction->reference_type === 'payment') {
                $invoice = $transaction->reference;
                if ($invoice && $invoice->apartment) {
                    if (!str_starts_with($transaction->description, 'Apto ')) {
                        $newDescription = "Apto {$invoice->apartment->number} - Pago factura {$invoice->invoice_number}";
                    }
                }
            }

            if ($newDescription && $newDescription !== $transaction->description) {
                $this->line("Transaction {$transaction->transaction_number}:");
                $this->line("  Old: {$transaction->description}");
                $this->line("  New: {$newDescription}");
                
                if (!$dryRun) {
                    $transaction->update(['description' => $newDescription]);
                }
                $updated++;
            }
        }

        if ($dryRun) {
            $this->info("Would update {$updated} transaction descriptions");
            $this->info("Run without --dry-run to apply changes");
        } else {
            $this->info("Updated {$updated} transaction descriptions");
        }

        return 0;
    }
}
