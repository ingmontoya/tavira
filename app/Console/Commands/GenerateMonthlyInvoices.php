<?php

namespace App\Console\Commands;

use App\Exceptions\InvoiceGenerationException;
use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateMonthlyInvoices extends Command
{
    protected $signature = 'invoices:generate-monthly 
                           {--year= : The year for which to generate invoices (defaults to current year)}
                           {--month= : The month for which to generate invoices (defaults to current month)}
                           {--force : Force generation even if invoices already exist}';

    protected $description = 'Generate monthly invoices for all eligible apartments based on administration fees';

    public function handle()
    {
        try {
            $year = $this->option('year') ?? now()->year;
            $month = $this->option('month') ?? now()->month;
            $force = $this->option('force');

            $this->info("Generating monthly invoices for {$year}-{$month}...");

            return DB::transaction(function () use ($year, $month, $force) {
                $this->validateInputs($year, $month);
                $conjunto = $this->getActiveConjunto();

                $this->handleExistingInvoices($conjunto, $year, $month, $force);
                $apartments = $this->getOccupiedApartments($conjunto);

                return $this->generateInvoices($conjunto, $apartments, $year, $month);
            });
        } catch (InvoiceGenerationException $e) {
            $this->error($e->getMessage());

            return Command::FAILURE;
        } catch (\Exception $e) {
            $this->error('Error inesperado durante la generación de facturas: '.$e->getMessage());
            $this->error('Trace: '.$e->getTraceAsString());

            return Command::FAILURE;
        }
    }

    private function validateInputs(int $year, int $month): void
    {
        if ($year < 2020 || $year > 2030) {
            throw new InvoiceGenerationException('El año debe estar entre 2020 y 2030.', 422);
        }

        if ($month < 1 || $month > 12) {
            throw new InvoiceGenerationException('El mes debe estar entre 1 y 12.', 422);
        }
    }

    private function getActiveConjunto(): ConjuntoConfig
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        if (! $conjunto) {
            throw InvoiceGenerationException::noActiveConjunto();
        }

        return $conjunto;
    }

    private function handleExistingInvoices(ConjuntoConfig $conjunto, int $year, int $month, bool $force): void
    {
        $existingInvoices = Invoice::where('conjunto_config_id', $conjunto->id)
            ->where('type', 'monthly')
            ->where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->count();

        if ($existingInvoices > 0 && ! $force) {
            throw InvoiceGenerationException::duplicatePeriod($year, $month, $existingInvoices);
        }

        if ($existingInvoices > 0 && $force) {
            $this->warn("Eliminando {$existingInvoices} facturas existentes para {$year}-{$month}...");

            $deleted = Invoice::where('conjunto_config_id', $conjunto->id)
                ->where('type', 'monthly')
                ->where('billing_period_year', $year)
                ->where('billing_period_month', $month)
                ->delete();

            $this->info("Se eliminaron {$deleted} facturas existentes.");
        }
    }

    private function getOccupiedApartments(ConjuntoConfig $conjunto)
    {
        $apartments = Apartment::where('conjunto_config_id', $conjunto->id)
            ->whereIn('status', ['Occupied', 'Available'])
            ->with('apartmentType')
            ->get();

        if ($apartments->isEmpty()) {
            throw InvoiceGenerationException::noOccupiedApartments();
        }

        return $apartments;
    }

    private function generateInvoices($conjunto, $apartments, int $year, int $month): int
    {
        $billingDate = now();
        $periodStart = Carbon::createFromDate($year, $month, 1);
        $periodEnd = $periodStart->copy()->endOfMonth();
        $dueDate = $periodEnd->copy(); // Due date is the last day of the billing period month

        $generatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $this->info("Procesando {$apartments->count()} apartamentos elegibles...");

        $progressBar = $this->output->createProgressBar($apartments->count());
        $progressBar->start();

        foreach ($apartments as $apartment) {
            try {
                // Skip apartments without administration fee
                if (! $apartment->monthly_fee || $apartment->monthly_fee <= 0) {
                    $this->warn("Apartamento {$apartment->number} no tiene cuota de administración configurada.");
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

                // Create invoice item for administration fee
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'payment_concept_id' => null, // No payment concept needed for administration
                    'description' => "Cuota de Administración - {$apartment->apartmentType->name}",
                    'quantity' => 1,
                    'unit_price' => $apartment->monthly_fee,
                    'period_start' => $periodStart,
                    'period_end' => $periodEnd,
                ]);

                $invoice->calculateTotals();
                $generatedCount++;
            } catch (\Exception $e) {
                $this->error("Error generando factura para apartamento {$apartment->number}: {$e->getMessage()}");
                $errorCount++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("Facturas generadas exitosamente: {$generatedCount}");

        if ($skippedCount > 0) {
            $this->warn("Apartamentos omitidos (sin conceptos aplicables): {$skippedCount}");
        }

        if ($errorCount > 0) {
            $this->error("Errores durante la generación: {$errorCount}");
        }

        $this->info("Período de facturación: {$periodStart->format('M Y')}");
        $this->info("Fecha de vencimiento: {$dueDate->format('Y-m-d')}");

        return Command::SUCCESS;
    }
}
