<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\LateFeesService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessMonthlyLateFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'late-fees:process {--dry-run : Run without making changes} {--date= : Process for specific date (Y-m-d)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process monthly late fees for overdue invoices';

    private LateFeesService $lateFeesService;

    public function __construct(LateFeesService $lateFeesService)
    {
        parent::__construct();
        $this->lateFeesService = $lateFeesService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        $processDate = $this->option('date') ? Carbon::parse($this->option('date')) : now();

        $this->info("Procesando intereses de mora para fecha: {$processDate->format('Y-m-d')}");

        if ($isDryRun) {
            $this->warn('MODO SIMULACIÓN - No se realizarán cambios');
        }

        // Obtener facturas vencidas que necesitan cálculo de mora
        $overdueInvoices = Invoice::overdue()
            ->whereIn('status', ['vencido', 'pago_parcial'])
            ->where('balance_amount', '>', 0)
            ->get();

        $this->info("Encontradas {$overdueInvoices->count()} facturas vencidas para procesar");

        if ($overdueInvoices->isEmpty()) {
            $this->info('No hay facturas vencidas para procesar');

            return 0;
        }

        $processed = 0;
        $errors = 0;
        $totalLateFees = 0;

        foreach ($overdueInvoices as $invoice) {
            try {
                $this->line("Procesando factura: {$invoice->invoice_number} - Apartamento {$invoice->apartment->number}");

                $result = $this->lateFeesService->processMonthlyLateFee($invoice, $processDate, $isDryRun);

                if ($result['applied']) {
                    $processed++;
                    $totalLateFees += $result['late_fee_amount'];
                    $this->info('  ✓ Mora aplicada: $'.number_format($result['late_fee_amount'], 2));
                } else {
                    $this->line('  - '.$result['reason']);
                }

            } catch (\Exception $e) {
                $errors++;
                $this->error("  ✗ Error procesando factura {$invoice->invoice_number}: ".$e->getMessage());
            }
        }

        $this->newLine();
        $this->info('Resumen del procesamiento:');
        $this->info("- Facturas procesadas: {$processed}");
        $this->info('- Total intereses aplicados: $'.number_format($totalLateFees, 2));

        if ($errors > 0) {
            $this->error("- Errores: {$errors}");
        }

        return $errors > 0 ? 1 : 0;
    }
}
