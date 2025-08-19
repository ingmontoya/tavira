<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\ElectronicInvoicingService;
use Illuminate\Console\Command;

class EvaluateElectronicInvoicing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:evaluate-electronic {--force : Force re-evaluation of all invoices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Evaluate existing invoices for electronic invoicing eligibility';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Evaluating invoices for electronic invoicing eligibility...');

        try {
            $electronicInvoicingService = app(ElectronicInvoicingService::class);

            // Get invoices to evaluate
            $query = Invoice::with(['apartment.residents']);

            if (! $this->option('force')) {
                // Only evaluate invoices that haven't been evaluated yet
                $query->whereNull('can_be_electronic');
            }

            $invoices = $query->get();

            if ($invoices->isEmpty()) {
                $this->info('No invoices to evaluate.');

                return 0;
            }

            $this->info("Found {$invoices->count()} invoices to evaluate.");

            $progressBar = $this->output->createProgressBar($invoices->count());
            $progressBar->start();

            $eligibleCount = 0;
            $notEligibleCount = 0;
            $errorCount = 0;

            foreach ($invoices as $invoice) {
                try {
                    // Check if electronic invoicing should be used for this invoice
                    $shouldUse = $electronicInvoicingService->shouldUseElectronicInvoicing($invoice);

                    $invoice->update([
                        'can_be_electronic' => $shouldUse,
                        'electronic_invoice_status' => $shouldUse ? 'pending' : null,
                    ]);

                    if ($shouldUse) {
                        $eligibleCount++;
                    } else {
                        $notEligibleCount++;
                    }

                } catch (\Exception $e) {
                    // If there's an error evaluating, default to false
                    $invoice->update(['can_be_electronic' => false]);
                    $errorCount++;
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info('Evaluation completed!');
            $this->info("✅ Eligible for electronic invoicing: {$eligibleCount}");
            $this->info("❌ Not eligible: {$notEligibleCount}");

            if ($errorCount > 0) {
                $this->warn("⚠️  Errors during evaluation: {$errorCount}");
            }

            return 0;

        } catch (\Exception $e) {
            $this->error('Error during evaluation: '.$e->getMessage());

            return 1;
        }
    }
}
