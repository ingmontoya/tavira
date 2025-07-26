<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Settings\PaymentSettings;
use Illuminate\Console\Command;

class ProcessLateFees extends Command
{
    protected $signature = 'invoices:process-late-fees';

    protected $description = 'Process and update late fees for overdue invoices';

    public function handle()
    {
        $paymentSettings = app(PaymentSettings::class);

        if (! $paymentSettings->late_fees_enabled) {
            $this->info('Late fees are disabled. Skipping processing.');

            return Command::SUCCESS;
        }

        $overdueInvoices = Invoice::where('status', '!=', 'paid')
            ->where('status', '!=', 'cancelled')
            ->where('due_date', '<', now())
            ->get();

        $processed = 0;

        foreach ($overdueInvoices as $invoice) {
            $oldLateFees = $invoice->late_fees;
            $invoice->updateLateFees();

            if ($invoice->late_fees != $oldLateFees) {
                $processed++;
                $this->line("Updated late fees for Invoice #{$invoice->invoice_number}: ${$oldLateFees} -> ${$invoice->late_fees}");
            }
        }

        $this->info("Processed {$processed} invoices with updated late fees.");

        return Command::SUCCESS;
    }
}
