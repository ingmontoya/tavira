<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Services\WithholdingCertificateService;
use Illuminate\Console\Command;

class GenerateYearlyWithholdingCertificates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'retenciones:generar-certificados {year? : Año para generar certificados (por defecto año anterior)} {--provider-id= : ID del proveedor específico}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Genera certificados de retención en la fuente para todos los proveedores con retenciones en el año especificado';

    /**
     * Execute the console command.
     */
    public function handle(WithholdingCertificateService $service)
    {
        $year = $this->argument('year') ?? now()->subYear()->year;
        $providerId = $this->option('provider-id');

        $this->info("Generando certificados de retención para el año {$year}...");

        // Build query for providers with withholdings in the year
        $providersQuery = Provider::whereHas('expenses', function ($query) use ($year) {
            $query->whereYear('expense_date', $year)
                ->where('tax_amount', '>', 0)
                ->whereNotNull('tax_account_id');
        });

        // Filter by specific provider if requested
        if ($providerId) {
            $providersQuery->where('id', $providerId);
        }

        $providers = $providersQuery->get();

        if ($providers->isEmpty()) {
            $this->warn('No se encontraron proveedores con retenciones para el año '.$year);

            return Command::SUCCESS;
        }

        $this->info("Se encontraron {$providers->count()} proveedores con retenciones.");
        $this->newLine();

        $bar = $this->output->createProgressBar($providers->count());
        $bar->start();

        $generated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($providers as $provider) {
            try {
                // Check if certificate already exists
                $existingCertificate = $provider->withholdingCertificates()
                    ->where('year', $year)
                    ->first();

                if ($existingCertificate) {
                    $this->newLine();
                    $this->warn("  ⏭  {$provider->name} - Ya existe certificado: {$existingCertificate->certificate_number}");
                    $skipped++;
                } else {
                    $certificate = $service->generateForProvider($provider, $year);

                    if ($certificate) {
                        $this->newLine();
                        $this->info("  ✓  {$provider->name} - Certificado: {$certificate->certificate_number} - Total retenido: $".number_format($certificate->total_retained, 2));
                        $generated++;
                    } else {
                        $this->newLine();
                        $this->warn("  ⏭  {$provider->name} - Sin retenciones en el período");
                        $skipped++;
                    }
                }
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("  ✗  {$provider->name} - Error: {$e->getMessage()}");
                $errors++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        // Summary
        $this->info('═══════════════════════════════════════════════════════');
        $this->info("Resumen de generación de certificados - Año {$year}");
        $this->info('═══════════════════════════════════════════════════════');
        $this->line("  Certificados generados:  <fg=green>{$generated}</>");
        $this->line("  Certificados omitidos:   <fg=yellow>{$skipped}</>");
        $this->line("  Errores:                 <fg=red>{$errors}</>");
        $this->info('═══════════════════════════════════════════════════════');

        return Command::SUCCESS;
    }
}
