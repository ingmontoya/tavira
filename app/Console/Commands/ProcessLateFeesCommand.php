<?php

namespace App\Console\Commands;

use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\PaymentConcept;
use Illuminate\Console\Command;

class ProcessLateFeesCommand extends Command
{
    protected $signature = 'invoices:process-late-fees 
                           {--rate=2.5 : The monthly late fee rate as percentage (default: 2.5%)}
                           {--grace-days=5 : Grace period in days after due date (default: 5 days)}
                           {--dry-run : Show what would be processed without making changes}';

    protected $description = 'Process late fees for overdue invoices and update apartment payment status';

    public function handle()
    {
        $lateRate = (float) $this->option('rate');
        $graceDays = (int) $this->option('grace-days');
        $dryRun = $this->option('dry-run');

        $this->info("Processing late fees with {$lateRate}% monthly rate and {$graceDays} grace days...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No changes will be made');
        }

        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        if (! $conjunto) {
            $this->error('No active conjunto configuration found.');

            return Command::FAILURE;
        }

        $lateFeesConcept = PaymentConcept::where('conjunto_config_id', $conjunto->id)
            ->where('type', 'late_fee')
            ->where('is_active', true)
            ->first();

        if (! $lateFeesConcept) {
            $this->warn('No active late fees payment concept found. Creating one...');

            if (! $dryRun) {
                $lateFeesConcept = PaymentConcept::create([
                    'conjunto_config_id' => $conjunto->id,
                    'name' => 'Interés de mora',
                    'description' => 'Intereses por mora en el pago de administración',
                    'type' => 'late_fee',
                    'default_amount' => 0,
                    'is_recurring' => false,
                    'is_active' => true,
                    'billing_cycle' => 'one_time',
                ]);

                $this->info('Late fees concept created successfully.');
            }
        }

        $cutoffDate = now()->subDays($graceDays);

        $overdueInvoices = Invoice::with(['apartment'])
            ->where('conjunto_config_id', $conjunto->id)
            ->where('status', 'pending')
            ->where('due_date', '<', $cutoffDate)
            ->get();

        if ($overdueInvoices->isEmpty()) {
            $this->info('No overdue invoices found.');

            return Command::SUCCESS;
        }

        $this->info("Found {$overdueInvoices->count()} overdue invoices.");

        $processedCount = 0;
        $totalLateFees = 0;

        $progressBar = $this->output->createProgressBar($overdueInvoices->count());
        $progressBar->start();

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = $invoice->due_date->diffInDays(now());
            $monthsOverdue = max(1, ceil($daysOverdue / 30));

            $lateFeeAmount = $invoice->subtotal * ($lateRate / 100) * $monthsOverdue;
            $lateFeeAmount = round($lateFeeAmount, 2);

            if ($lateFeeAmount > 0) {
                $this->line("\nApartment {$invoice->apartment->full_address}: {$lateFeeAmount} ({$daysOverdue} days overdue)");

                if (! $dryRun) {
                    $invoice->update([
                        'late_fees' => $lateFeeAmount,
                        'status' => 'overdue',
                    ]);

                    $invoice->calculateTotals();

                    $invoice->apartment->updatePaymentStatus();
                }

                $processedCount++;
                $totalLateFees += $lateFeeAmount;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        if ($dryRun) {
            $this->info("DRY RUN: Would process {$processedCount} invoices with total late fees of {$totalLateFees}");
        } else {
            $this->info("Processed {$processedCount} overdue invoices.");
            $this->info("Total late fees applied: {$totalLateFees}");

            $overdueInvoices->each(function ($invoice) {
                $invoice->apartment->updatePaymentStatus();
            });

            $this->info('Updated apartment payment statuses.');
        }

        return Command::SUCCESS;
    }
}
