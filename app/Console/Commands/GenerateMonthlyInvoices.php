<?php

namespace App\Console\Commands;

use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentConcept;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'invoices:generate-monthly 
                           {--year= : The year for which to generate invoices (defaults to current year)}
                           {--month= : The month for which to generate invoices (defaults to current month)}
                           {--force : Force generation even if invoices already exist}';

    protected $description = 'Generate monthly invoices for all occupied apartments based on recurring payment concepts';

    public function handle()
    {
        $year = $this->option('year') ?? now()->year;
        $month = $this->option('month') ?? now()->month;
        $force = $this->option('force');

        $this->info("Generating monthly invoices for {$year}-{$month}...");

        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        if (!$conjunto) {
            $this->error('No active conjunto configuration found.');
            return Command::FAILURE;
        }

        $existingInvoices = Invoice::where('conjunto_config_id', $conjunto->id)
            ->where('type', 'monthly')
            ->where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->count();

        if ($existingInvoices > 0 && !$force) {
            $this->error("Monthly invoices for {$year}-{$month} already exist. Use --force to regenerate.");
            return Command::FAILURE;
        }

        if ($existingInvoices > 0 && $force) {
            $this->warn("Deleting existing invoices for {$year}-{$month}...");
            Invoice::where('conjunto_config_id', $conjunto->id)
                ->where('type', 'monthly')
                ->where('billing_period_year', $year)
                ->where('billing_period_month', $month)
                ->delete();
        }

        $apartments = Apartment::where('conjunto_config_id', $conjunto->id)
            ->where('status', 'Occupied')
            ->with('apartmentType')
            ->get();

        if ($apartments->isEmpty()) {
            $this->warn('No occupied apartments found.');
            return Command::SUCCESS;
        }

        $commonExpenseConcepts = PaymentConcept::where('conjunto_config_id', $conjunto->id)
            ->where('type', 'common_expense')
            ->where('is_active', true)
            ->where('is_recurring', true)
            ->where('billing_cycle', 'monthly')
            ->get();

        if ($commonExpenseConcepts->isEmpty()) {
            $this->warn('No active recurring monthly payment concepts found.');
            return Command::SUCCESS;
        }

        $billingDate = now();
        $dueDate = $billingDate->copy()->addDays(15);
        $periodStart = Carbon::createFromDate($year, $month, 1);
        $periodEnd = $periodStart->copy()->endOfMonth();

        $generatedCount = 0;
        $skippedCount = 0;

        $this->info("Processing {$apartments->count()} occupied apartments...");

        $progressBar = $this->output->createProgressBar($apartments->count());
        $progressBar->start();

        foreach ($apartments as $apartment) {
            $applicableConcepts = $commonExpenseConcepts->filter(function ($concept) use ($apartment) {
                return $concept->isApplicableToApartmentType($apartment->apartment_type_id);
            });

            if ($applicableConcepts->isEmpty()) {
                $skippedCount++;
                $progressBar->advance();
                continue;
            }

            $invoice = Invoice::create([
                'conjunto_config_id' => $conjunto->id,
                'apartment_id' => $apartment->id,
                'type' => 'monthly',
                'billing_date' => $billingDate,
                'due_date' => $dueDate,
                'billing_period_year' => $year,
                'billing_period_month' => $month,
            ]);

            foreach ($applicableConcepts as $concept) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'payment_concept_id' => $concept->id,
                    'description' => $concept->name,
                    'quantity' => 1,
                    'unit_price' => $concept->default_amount,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ]);
            }

            $invoice->calculateTotals();
            $generatedCount++;
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Successfully generated {$generatedCount} monthly invoices.");
        
        if ($skippedCount > 0) {
            $this->warn("Skipped {$skippedCount} apartments (no applicable payment concepts).");
        }

        $this->info("Invoices generated for period: {$periodStart->format('M Y')}");
        $this->info("Due date: {$dueDate->format('Y-m-d')}");

        return Command::SUCCESS;
    }
}
