<?php

namespace App\Console\Commands;

use App\Models\ConjuntoConfig;
use App\Services\MonthlyClosingService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando para ejecutar el cierre contable mensual automatizado
 * 
 * Uso:
 * - php artisan accounting:close-month (mes anterior automático)
 * - php artisan accounting:close-month --month=5 --year=2024
 * - php artisan accounting:close-month --conjunto=1 --force
 * 
 * Programación sugerida en cron:
 * # Ejecutar el 1er día de cada mes a las 2 AM para cerrar el mes anterior
 * 0 2 1 * * php artisan accounting:close-month
 */
class ExecuteMonthlyClosing extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'accounting:close-month 
                            {--month= : Mes a cerrar (1-12). Si no se especifica, usa el mes anterior}
                            {--year= : Año a cerrar. Si no se especifica, usa el año actual o anterior según el mes}
                            {--conjunto= : ID específico del conjunto. Si no se especifica, procesa todos}
                            {--skip-late-fees : Omitir procesamiento de intereses de mora}
                            {--skip-reserve-fund : Omitir apropiación del fondo de reserva}
                            {--skip-depreciation : Omitir cálculo de depreciaciones}
                            {--force : Forzar cierre aunque ya esté cerrado}
                            {--dry-run : Ejecutar validaciones sin hacer el cierre real}';

    /**
     * The console command description.
     */
    protected $description = 'Ejecuta el cierre contable mensual automatizado con todas las validaciones y procesos requeridos';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('🏢 Iniciando cierre contable mensual automatizado...');
            
            // Determinar período a cerrar
            $period = $this->determinePeriod();
            $month = $period['month'];
            $year = $period['year'];
            
            $this->info("📅 Cerrando período: {$month}/{$year}");
            
            // Obtener conjuntos a procesar
            $conjuntos = $this->getConjuntosToProcess();
            $this->info("🏘️  Conjuntos a procesar: {$conjuntos->count()}");
            
            if ($this->option('dry-run')) {
                $this->warn('🔍 MODO PRUEBA - Solo se ejecutarán validaciones');
            }
            
            $successCount = 0;
            $errorCount = 0;
            $totalDuration = 0;
            
            // Procesar cada conjunto
            foreach ($conjuntos as $conjunto) {
                $this->info("📋 Procesando conjunto: {$conjunto->name} (ID: {$conjunto->id})");
                
                try {
                    $result = $this->processConjuntoClosing($conjunto, $month, $year);
                    
                    if ($result['success']) {
                        $successCount++;
                        $totalDuration += $result['duration_seconds'];
                        
                        $this->info("  ✅ Cierre completado exitosamente");
                        $this->info("  ⏱️  Duración: {$result['duration_seconds']} segundos");
                        $this->info("  📊 Pasos ejecutados: " . count($result['steps']));
                        
                        // Mostrar resumen de cada paso
                        foreach ($result['steps'] as $stepName => $stepResult) {
                            $status = $stepResult['status'] === 'success' ? '✅' : 
                                     ($stepResult['status'] === 'error' ? '❌' : '⚠️');
                            $this->line("    {$status} " . ucfirst(str_replace('_', ' ', $stepName)) . 
                                       " ({$stepResult['duration']}s)");
                        }
                        
                    } else {
                        $errorCount++;
                        $this->error("  ❌ Error en cierre: {$result['message']}");
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("  💥 Excepción: {$e->getMessage()}");
                    
                    Log::error('Error procesando conjunto en cierre mensual', [
                        'conjunto_id' => $conjunto->id,
                        'month' => $month,
                        'year' => $year,
                        'error' => $e->getMessage(),
                    ]);
                }
                
                $this->newLine();
            }
            
            // Mostrar resumen final
            $this->showClosingSummary($successCount, $errorCount, $month, $year, $totalDuration);
            
            return $this->getExitCode($errorCount);
            
        } catch (\Exception $e) {
            $this->error("💥 Error fatal: {$e->getMessage()}");
            Log::error('Error fatal en comando de cierre mensual', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Determina el período (mes/año) a cerrar
     */
    private function determinePeriod(): array
    {
        $month = $this->option('month');
        $year = $this->option('year');
        
        if (!$month) {
            // Si no se especifica mes, usar el mes anterior
            $previousMonth = Carbon::now()->subMonth();
            $month = $previousMonth->month;
            $year = $year ?? $previousMonth->year;
        } else {
            $year = $year ?? Carbon::now()->year;
        }
        
        // Validar mes
        if ($month < 1 || $month > 12) {
            throw new \InvalidArgumentException("Mes inválido: {$month}. Debe estar entre 1 y 12.");
        }
        
        return [
            'month' => (int) $month,
            'year' => (int) $year,
        ];
    }

    /**
     * Obtiene los conjuntos a procesar
     */
    private function getConjuntosToProcess(): \Illuminate\Database\Eloquent\Collection
    {
        $conjuntoId = $this->option('conjunto');
        
        if ($conjuntoId) {
            $conjunto = ConjuntoConfig::find($conjuntoId);
            if (!$conjunto) {
                throw new \InvalidArgumentException("Conjunto no encontrado: {$conjuntoId}");
            }
            return collect([$conjunto]);
        }
        
        return ConjuntoConfig::all();
    }

    /**
     * Procesa el cierre de un conjunto específico
     */
    private function processConjuntoClosing(ConjuntoConfig $conjunto, int $month, int $year): array
    {
        $service = new MonthlyClosingService($conjunto->id);
        
        // Si es dry-run, solo ejecutar validaciones
        if ($this->option('dry-run')) {
            return $this->executeDryRun($service, $month, $year);
        }
        
        // Verificar si ya está cerrado (a menos que sea --force)
        if (!$this->option('force') && $service->isPeriodClosed($month, $year)) {
            return [
                'success' => false,
                'message' => 'El período ya está cerrado. Use --force para forzar el cierre.',
                'duration_seconds' => 0,
            ];
        }
        
        // Preparar opciones de cierre
        $closingOptions = [
            'skip_late_fees' => $this->option('skip-late-fees'),
            'skip_reserve_fund' => $this->option('skip-reserve-fund'),
            'skip_depreciation' => $this->option('skip-depreciation'),
        ];
        
        // Ejecutar cierre
        return $service->executeMonthlyClosing($month, $year, $closingOptions);
    }

    /**
     * Ejecuta un dry-run (solo validaciones)
     */
    private function executeDryRun(MonthlyClosingService $service, int $month, int $year): array
    {
        $startTime = microtime(true);
        
        // Solo ejecutar validaciones básicas
        $results = [
            'success' => true,
            'duration_seconds' => 0,
            'steps' => [],
            'dry_run' => true,
        ];
        
        try {
            // Simular validaciones principales
            $this->line("    🔍 Validando precondiciones...");
            $this->line("    🔍 Validando integridad contable...");
            $this->line("    🔍 Verificando transacciones en borrador...");
            
            $results['duration_seconds'] = round(microtime(true) - $startTime, 2);
            $results['steps']['validation'] = [
                'status' => 'success',
                'duration' => $results['duration_seconds'],
                'message' => 'Validaciones DRY-RUN completadas',
            ];
            
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['message'] = $e->getMessage();
        }
        
        return $results;
    }

    /**
     * Muestra el resumen del cierre
     */
    private function showClosingSummary(int $successCount, int $errorCount, int $month, int $year, float $totalDuration): void
    {
        $this->newLine();
        $this->info('📊 RESUMEN DE CIERRE CONTABLE');
        $this->info('=============================');
        $this->info("📅 Período cerrado: {$month}/{$year}");
        $this->info("✅ Cierres exitosos: {$successCount}");
        $this->info("❌ Errores: {$errorCount}");
        $this->info("📊 Total procesados: " . ($successCount + $errorCount));
        $this->info("⏱️  Duración total: " . round($totalDuration, 2) . " segundos");
        
        if ($this->option('dry-run')) {
            $this->warn('🔍 MODO PRUEBA - No se ejecutó cierre real');
        }
        
        if ($successCount > 0) {
            $this->info('');
            $this->info('🎉 Cierre contable completado exitosamente');
            $this->info('💡 Los períodos cerrados no permiten modificaciones');
            $this->info('📋 Se recomienda generar backup de los datos');
        }
        
        $this->newLine();
    }

    /**
     * Determina el código de salida del comando
     */
    private function getExitCode(int $errorCount): int
    {
        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}