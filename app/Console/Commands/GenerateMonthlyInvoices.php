<?php

namespace App\Console\Commands;

use App\Events\InvoiceCreated;
use App\Exceptions\InvoiceGenerationException;
use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PaymentConcept;
use App\Settings\PaymentSettings;
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
        $existingInvoices = Invoice::where('type', 'monthly')
            ->where('billing_period_year', $year)
            ->where('billing_period_month', $month)
            ->count();

        if ($existingInvoices > 0 && ! $force) {
            throw InvoiceGenerationException::duplicatePeriod($year, $month, $existingInvoices);
        }

        if ($existingInvoices > 0 && $force) {
            $this->warn("Eliminando {$existingInvoices} facturas existentes para {$year}-{$month}...");

            // Obtener los IDs de las facturas antes de eliminarlas
            $invoiceIds = Invoice::where('type', 'monthly')
                ->where('billing_period_year', $year)
                ->where('billing_period_month', $month)
                ->pluck('id');

            // Eliminar las transacciones contables asociadas
            $deletedTransactions = \App\Models\AccountingTransaction::where('reference_type', 'invoice')
                ->whereIn('reference_id', $invoiceIds)
                ->delete();

            // Eliminar las facturas
            $deleted = Invoice::where('type', 'monthly')
                ->where('billing_period_year', $year)
                ->where('billing_period_month', $month)
                ->delete();

            $this->info("Se eliminaron {$deleted} facturas y {$deletedTransactions} transacciones contables existentes.");
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

        // Get payment settings for late fee calculation
        $paymentSettings = app(PaymentSettings::class);

        // Obtener o crear el concepto de Administración Mensual
        $monthlyAdminConcept = PaymentConcept::firstOrCreate(
            [
                'name' => 'Administración Mensual',
                'type' => 'monthly_administration',
            ],
            [
                'description' => 'Cuota de administración mensual base por tipo de apartamento',
                'default_amount' => 0,
                'is_recurring' => true,
                'is_active' => true,
                'billing_cycle' => 'monthly',
                'applicable_apartment_types' => null,
            ]
        );

        // Obtener o crear el concepto de Interés de Mora
        $lateFeeConcept = PaymentConcept::firstOrCreate(
            [
                'name' => 'Interés de Mora',
                'type' => 'late_fee',
            ],
            [
                'description' => 'Intereses por mora en el pago de obligaciones',
                'default_amount' => 0,
                'is_recurring' => false,
                'is_active' => true,
                'billing_cycle' => 'one_time',
                'applicable_apartment_types' => null,
            ]
        );

        // Obtener o crear el concepto de Cuota Extraordinaria
        $extraordinaryConcept = PaymentConcept::firstOrCreate(
            [
                'name' => 'Cuota Extraordinaria',
                'type' => 'extraordinary_assessment',
            ],
            [
                'description' => 'Cuota extraordinaria para proyectos especiales',
                'default_amount' => 0,
                'is_recurring' => false,
                'is_active' => true,
                'billing_cycle' => 'one_time',
                'applicable_apartment_types' => null,
                'conjunto_config_id' => $conjunto->id,
            ]
        );

        // Obtener cuotas extraordinarias activas para este período
        $extraordinaryAssessments = \App\Models\ExtraordinaryAssessment::forCurrentMonth($periodStart)
            ->with('apartments')
            ->get();

        $this->info("Cuotas extraordinarias activas: {$extraordinaryAssessments->count()}");

        // Calculate previous billing period
        $previousPeriodDate = $periodStart->copy()->subMonth();
        $previousYear = $previousPeriodDate->year;
        $previousMonth = $previousPeriodDate->month;

        $generatedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        $lateFeesAppliedCount = 0;
        $extraordinaryItemsCount = 0;

        $this->info("Procesando {$apartments->count()} apartamentos elegibles...");
        $this->info("Usando concepto: {$monthlyAdminConcept->name} (ID: {$monthlyAdminConcept->id})");
        $this->info("Verificando facturas del período anterior: {$previousYear}-{$previousMonth}");

        $progressBar = $this->output->createProgressBar($apartments->count());
        $progressBar->start();

        // Process apartments in chunks to avoid memory issues
        $apartments->chunk(50)->each(function ($apartmentChunk) use (
            &$generatedCount, &$skippedCount, &$errorCount, &$lateFeesAppliedCount, &$extraordinaryItemsCount, $progressBar,
            $billingDate, $dueDate, $year, $month, $periodStart, $periodEnd, $monthlyAdminConcept,
            $lateFeeConcept, $extraordinaryConcept, $paymentSettings, $previousYear, $previousMonth,
            $extraordinaryAssessments
        ) {
            foreach ($apartmentChunk as $apartment) {
                try {
                    // Skip apartments without administration fee
                    if (! $apartment->monthly_fee || $apartment->monthly_fee <= 0) {
                        $this->warn("Apartamento {$apartment->number} no tiene cuota de administración configurada.");
                        $skippedCount++;
                        $progressBar->advance();

                        continue;
                    }

                    // Check for unpaid invoice from previous month
                    $previousInvoice = Invoice::where('apartment_id', $apartment->id)
                        ->where('type', 'monthly')
                        ->where('billing_period_year', $previousYear)
                        ->where('billing_period_month', $previousMonth)
                        ->first();

                    $lateFeeAmount = 0;
                    $shouldApplyLateFee = false;

                    if ($previousInvoice && $previousInvoice->status !== 'pagado' && $paymentSettings->late_fees_enabled) {
                        // Invoice from previous month exists and is not paid
                        $gracePeriodEnd = $previousInvoice->due_date->copy()->addDays($paymentSettings->grace_period_days);

                        // Check if grace period has ended
                        if ($billingDate->greaterThan($gracePeriodEnd)) {
                            // IMPORTANTE: Calcular mora SOLO sobre el capital (excluyendo moras previas)
                            // No se permite cobrar intereses sobre intereses (anatocismo)
                            $capitalBalance = $previousInvoice->getCapitalBalance();
                            $lateFeeAmount = round($capitalBalance * ($paymentSettings->late_fee_percentage / 100), 2);
                            $shouldApplyLateFee = $lateFeeAmount > 0;
                        }
                    }

                    // Create the new invoice
                    $invoice = Invoice::create([
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
                        'payment_concept_id' => $monthlyAdminConcept->id,
                        'description' => "Cuota de Administración - {$apartment->apartmentType->name}",
                        'quantity' => 1,
                        'unit_price' => $apartment->monthly_fee,
                        'period_start' => $periodStart,
                        'period_end' => $periodEnd,
                    ]);

                    // Add late fee item if applicable
                    if ($shouldApplyLateFee) {
                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'payment_concept_id' => $lateFeeConcept->id,
                            'description' => "Interés de Mora - Factura {$previousInvoice->invoice_number} ({$previousYear}-{$previousMonth})",
                            'quantity' => 1,
                            'unit_price' => $lateFeeAmount,
                            'period_start' => $previousInvoice->due_date,
                            'period_end' => $billingDate,
                        ]);

                        $lateFeesAppliedCount++;

                        $this->line("  → Mora aplicada a Apt {$apartment->number}: $".number_format($lateFeeAmount, 2));
                    }

                    // Add extraordinary assessment items if applicable
                    foreach ($extraordinaryAssessments as $assessment) {
                        // Verificar si debe generar cuota para este mes
                        if (! $assessment->shouldGenerateInstallmentForMonth($periodStart)) {
                            continue;
                        }

                        // Buscar la asignación del apartamento
                        $assessmentApartment = $assessment->apartments()
                            ->where('apartment_id', $apartment->id)
                            ->first();

                        if ($assessmentApartment && $assessmentApartment->installment_amount > 0) {
                            InvoiceItem::create([
                                'invoice_id' => $invoice->id,
                                'payment_concept_id' => $extraordinaryConcept->id,
                                'description' => "Cuota Extraordinaria - {$assessment->name} (Cuota ".
                                    ($assessment->installments_generated + 1)."/{$assessment->number_of_installments})",
                                'quantity' => 1,
                                'unit_price' => $assessmentApartment->installment_amount,
                                'period_start' => $periodStart,
                                'period_end' => $periodEnd,
                            ]);

                            $extraordinaryItemsCount++;

                            $this->line("  → Cuota extraordinaria agregada a Apt {$apartment->number}: $".number_format($assessmentApartment->installment_amount, 2)." ({$assessment->name})");
                        }
                    }

                    $invoice->calculateTotals();

                    // Fire the InvoiceCreated event after invoice is fully populated
                    event(new InvoiceCreated($invoice));

                    $generatedCount++;
                } catch (\Exception $e) {
                    $this->error("Error generando factura para apartamento {$apartment->number}: {$e->getMessage()}");
                    $errorCount++;
                }

                $progressBar->advance();
            }

            // Clean up memory after each chunk
            gc_collect_cycles();
        });

        $progressBar->finish();
        $this->newLine();

        // Marcar las extraordinarias como generadas
        foreach ($extraordinaryAssessments as $assessment) {
            if ($assessment->shouldGenerateInstallmentForMonth($periodStart)) {
                $assessment->markInstallmentGenerated();
                $this->info("Cuota extraordinaria generada: {$assessment->name} (Cuota {$assessment->installments_generated}/{$assessment->number_of_installments})");
            }
        }

        $this->newLine();
        $this->info("Facturas generadas exitosamente: {$generatedCount}");

        if ($lateFeesAppliedCount > 0) {
            $this->warn("Facturas con mora aplicada: {$lateFeesAppliedCount}");
        }

        if ($extraordinaryItemsCount > 0) {
            $this->warn("Ítems de cuotas extraordinarias agregados: {$extraordinaryItemsCount}");
        }

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
