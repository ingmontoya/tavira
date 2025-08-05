<?php

namespace App\Console\Commands;

use App\Models\ConjuntoConfig;
use App\Services\ReserveFundService;
use App\Events\ReserveFundAppropriationCreated;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando para apropiación automática del fondo de reserva
 * 
 * Uso:
 * - php artisan reserve-fund:appropriate (mes anterior automático)
 * - php artisan reserve-fund:appropriate --month=5 --year=2024
 * - php artisan reserve-fund:appropriate --conjunto=1
 * 
 * Programación sugerida en cron:
 * # Ejecutar el 5to día de cada mes para el mes anterior
 * 0 6 5 * * php artisan reserve-fund:appropriate
 */
class AppropriateMonthlyReserveFund extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'reserve-fund:appropriate 
                            {--month= : Mes a procesar (1-12). Si no se especifica, usa el mes anterior}
                            {--year= : Año a procesar. Si no se especifica, usa el año actual o anterior según el mes}
                            {--conjunto= : ID específico del conjunto. Si no se especifica, procesa todos}
                            {--dry-run : Ejecutar en modo prueba sin crear transacciones}
                            {--force : Forzar ejecución aunque ya exista apropiación}';

    /**
     * The console command description.
     */
    protected $description = 'Ejecuta la apropiación automática del fondo de reserva mensual según Ley 675';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $this->info('🏢 Iniciando apropiación automática del fondo de reserva...');
            
            // Determinar período a procesar
            $period = $this->determinePeriod();
            $month = $period['month'];
            $year = $period['year'];
            
            $this->info("📅 Procesando período: {$month}/{$year}");
            
            // Obtener conjuntos a procesar
            $conjuntos = $this->getConjuntosToProcess();
            $this->info("🏘️  Conjuntos a procesar: {$conjuntos->count()}");
            
            $successCount = 0;
            $errorCount = 0;
            $skippedCount = 0;
            
            // Procesar cada conjunto
            foreach ($conjuntos as $conjunto) {
                $this->info("📋 Procesando conjunto: {$conjunto->name} (ID: {$conjunto->id})");
                
                try {
                    $result = $this->processConjunto($conjunto, $month, $year);
                    
                    if ($result['success']) {
                        $successCount++;
                        
                        if ($result['transaction']) {
                            $this->info("  ✅ Apropiación creada: {$result['transaction']->transaction_number}");
                            $this->info("  💰 Monto apropiado: $" . number_format($result['amount'], 2));
                        } else {
                            $this->info("  ℹ️  {$result['message']}");
                            $skippedCount++;
                        }
                    } else {
                        $errorCount++;
                        $this->error("  ❌ Error: {$result['message']}");
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("  💥 Excepción: {$e->getMessage()}");
                    
                    Log::error('Error procesando conjunto en apropiación de reserva', [
                        'conjunto_id' => $conjunto->id,
                        'month' => $month,
                        'year' => $year,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Mostrar resumen
            $this->showSummary($successCount, $errorCount, $skippedCount, $month, $year);
            
            return $this->getExitCode($errorCount);
            
        } catch (\Exception $e) {
            $this->error("💥 Error fatal: {$e->getMessage()}");
            Log::error('Error fatal en comando de apropiación de reserva', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return Command::FAILURE;
        }
    }

    /**
     * Determina el período (mes/año) a procesar
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
     * Procesa un conjunto específico
     */
    private function processConjunto(ConjuntoConfig $conjunto, int $month, int $year): array
    {
        $service = new ReserveFundService($conjunto->id);
        
        // Si es dry-run, solo calcular sin crear
        if ($this->option('dry-run')) {
            $amount = $service->calculateMonthlyReserve($month, $year);
            
            return [
                'success' => true,
                'transaction' => null,
                'amount' => $amount,
                'message' => "DRY-RUN: Se apropiarian $" . number_format($amount, 2),
            ];
        }
        
        // Verificar si ya existe apropiación (a menos que sea --force)
        if (!$this->option('force')) {
            $existing = $service->getAppropriationHistory($year)
                ->filter(function ($transaction) use ($month, $year) {
                    return $transaction->transaction_date->month === $month &&
                           $transaction->transaction_date->year === $year;
                });
                
            if ($existing->isNotEmpty()) {
                return [
                    'success' => true,
                    'transaction' => null,
                    'amount' => 0,
                    'message' => 'Ya existe apropiación para este período',
                ];
            }
        }
        
        // Ejecutar apropiación
        $transaction = $service->executeMonthlyAppropriation($month, $year);
        
        if ($transaction) {
            // Disparar evento para notificaciones adicionales
            $monthlyIncome = $service->calculateMonthlyReserve($month, $year) / 0.30; // Calcular ingresos base
            
            event(new ReserveFundAppropriationCreated(
                $transaction,
                $month,
                $year,
                (float) $transaction->total_debit,
                $monthlyIncome
            ));
            
            return [
                'success' => true,
                'transaction' => $transaction,
                'amount' => (float) $transaction->total_debit,
                'message' => 'Apropiación ejecutada exitosamente',
            ];
        }
        
        return [
            'success' => true,
            'transaction' => null,
            'amount' => 0,
            'message' => 'No hay monto para apropiar (ingresos insuficientes)',
        ];
    }

    /**
     * Muestra el resumen de la ejecución
     */
    private function showSummary(int $successCount, int $errorCount, int $skippedCount, int $month, int $year): void
    {
        $this->newLine();
        $this->info('📊 RESUMEN DE EJECUCIÓN');
        $this->info('========================');
        $this->info("📅 Período procesado: {$month}/{$year}");
        $this->info("✅ Éxitos: {$successCount}");
        $this->info("⏭️  Omitidos: {$skippedCount}");
        $this->info("❌ Errores: {$errorCount}");
        $this->info("📊 Total procesados: " . ($successCount + $errorCount));
        
        if ($this->option('dry-run')) {
            $this->warn('🔍 MODO PRUEBA - No se crearon transacciones reales');
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