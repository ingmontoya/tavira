<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

class QueueHealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:health';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Health check for queue workers (for Kubernetes liveness probes)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            // Verificar que podemos conectar con el sistema de colas
            $connection = config('queue.default');

            // Verificar conexión a Redis si se usa
            if ($connection === 'redis') {
                Cache::store('redis')->get('queue-health-check');
            }

            // Verificar que el driver de colas está disponible
            Queue::connection($connection)->size();

            $this->info('Queue system is healthy');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error('Queue system is unhealthy: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
