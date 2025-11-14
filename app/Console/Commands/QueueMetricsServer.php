<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class QueueMetricsServer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:metrics-server {--port=9090}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Start a simple HTTP server to expose queue metrics for Prometheus';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $port = $this->option('port');
        $this->info("Starting metrics server on port {$port}...");

        // Socket server
        $socket = stream_socket_server("tcp://0.0.0.0:{$port}", $errno, $errstr);

        if (! $socket) {
            $this->error("Failed to create socket: {$errstr} ({$errno})");

            return Command::FAILURE;
        }

        $this->info("Metrics server listening on http://0.0.0.0:{$port}/metrics");

        while (true) {
            $conn = @stream_socket_accept($socket, -1);
            if (! $conn) {
                continue;
            }

            $request = fread($conn, 1024);

            // Check if it's a GET request to /metrics
            if (strpos($request, 'GET /metrics') !== false) {
                $metrics = $this->generateMetrics();
                $response = "HTTP/1.1 200 OK\r\n";
                $response .= "Content-Type: text/plain; version=0.0.4\r\n";
                $response .= 'Content-Length: '.strlen($metrics)."\r\n";
                $response .= "Connection: close\r\n\r\n";
                $response .= $metrics;

                fwrite($conn, $response);
            } elseif (strpos($request, 'GET /health') !== false) {
                $response = "HTTP/1.1 200 OK\r\n";
                $response .= "Content-Type: text/plain\r\n";
                $response .= "Content-Length: 2\r\n";
                $response .= "Connection: close\r\n\r\n";
                $response .= 'OK';

                fwrite($conn, $response);
            } else {
                $response = "HTTP/1.1 404 Not Found\r\n";
                $response .= "Content-Length: 0\r\n";
                $response .= "Connection: close\r\n\r\n";

                fwrite($conn, $response);
            }

            fclose($conn);
        }

        return Command::SUCCESS;
    }

    /**
     * Generate Prometheus metrics
     */
    private function generateMetrics(): string
    {
        $metrics = [];

        try {
            // Queue sizes
            $connection = config('queue.default');

            if ($connection === 'redis') {
                $queues = ['default', 'tenant'];

                foreach ($queues as $queueName) {
                    $size = Redis::llen("queues:{$queueName}");
                    $metrics[] = '# HELP tavira_queue_size Number of jobs in queue';
                    $metrics[] = '# TYPE tavira_queue_size gauge';
                    $metrics[] = "tavira_queue_size{queue=\"{$queueName}\"} {$size}";
                }

                // Failed jobs count
                $failedCount = Redis::zcard('queues:default:failed');
                $metrics[] = '# HELP tavira_queue_failed_jobs Number of failed jobs';
                $metrics[] = '# TYPE tavira_queue_failed_jobs counter';
                $metrics[] = "tavira_queue_failed_jobs {$failedCount}";

                // Reserved jobs (being processed)
                $reservedCount = Redis::zcard('queues:default:reserved');
                $metrics[] = '# HELP tavira_queue_reserved_jobs Number of reserved jobs';
                $metrics[] = '# TYPE tavira_queue_reserved_jobs gauge';
                $metrics[] = "tavira_queue_reserved_jobs {$reservedCount}";
            } elseif ($connection === 'database') {
                // Database queue
                $defaultSize = DB::table('jobs')->where('queue', 'default')->count();
                $tenantSize = DB::table('jobs')->where('queue', 'tenant')->count();

                $metrics[] = '# HELP tavira_queue_size Number of jobs in queue';
                $metrics[] = '# TYPE tavira_queue_size gauge';
                $metrics[] = "tavira_queue_size{queue=\"default\"} {$defaultSize}";
                $metrics[] = "tavira_queue_size{queue=\"tenant\"} {$tenantSize}";

                // Failed jobs
                $failedCount = DB::table('failed_jobs')->count();
                $metrics[] = '# HELP tavira_queue_failed_jobs Number of failed jobs';
                $metrics[] = '# TYPE tavira_queue_failed_jobs counter';
                $metrics[] = "tavira_queue_failed_jobs {$failedCount}";
            }

            // Redis connection info
            if ($connection === 'redis') {
                try {
                    $info = Redis::info();
                    $connectedClients = $info['connected_clients'] ?? 0;
                    $usedMemory = $info['used_memory'] ?? 0;

                    $metrics[] = '# HELP tavira_redis_connected_clients Number of connected Redis clients';
                    $metrics[] = '# TYPE tavira_redis_connected_clients gauge';
                    $metrics[] = "tavira_redis_connected_clients {$connectedClients}";

                    $metrics[] = '# HELP tavira_redis_memory_bytes Redis memory usage in bytes';
                    $metrics[] = '# TYPE tavira_redis_memory_bytes gauge';
                    $metrics[] = "tavira_redis_memory_bytes {$usedMemory}";
                } catch (\Exception $e) {
                    // Redis info might not be available
                }
            }

            // Database connections (tenant count)
            try {
                $tenantCount = DB::connection('mysql')->table('tenants')->count();
                $metrics[] = '# HELP tavira_tenants_total Total number of tenants';
                $metrics[] = '# TYPE tavira_tenants_total gauge';
                $metrics[] = "tavira_tenants_total {$tenantCount}";
            } catch (\Exception $e) {
                // Central database might not be accessible
            }

            // Queue worker health
            $metrics[] = '# HELP tavira_queue_worker_up Queue worker is up';
            $metrics[] = '# TYPE tavira_queue_worker_up gauge';
            $metrics[] = 'tavira_queue_worker_up 1';

        } catch (\Exception $e) {
            // In case of error, still return basic metrics
            $metrics[] = '# HELP tavira_queue_worker_up Queue worker is up';
            $metrics[] = '# TYPE tavira_queue_worker_up gauge';
            $metrics[] = 'tavira_queue_worker_up 0';
            $metrics[] = '# HELP tavira_queue_error Queue metrics error';
            $metrics[] = '# TYPE tavira_queue_error gauge';
            $metrics[] = 'tavira_queue_error{error="'.addslashes($e->getMessage()).'"} 1';
        }

        return implode("\n", $metrics)."\n";
    }
}
