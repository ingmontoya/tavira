<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncPanicButtonFeaturesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'panic-button:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync panic button feature for all tenants to ensure visibility in admin dashboard';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn('No tenants found.');

            return Command::SUCCESS;
        }

        $this->info("Syncing panic button feature for {$tenants->count()} tenants...");

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($tenants as $tenant) {
            try {
                $tenantDbName = 'tenant'.$tenant->id;

                // Check if database exists
                $dbExists = DB::select('SELECT datname FROM pg_database WHERE datname = ?', [$tenantDbName]);

                if (empty($dbExists)) {
                    $this->error("\nDatabase for tenant {$tenant->id} does not exist. Skipping...");
                    $errorCount++;
                    $bar->advance();

                    continue;
                }

                // Configure tenant connection dynamically
                config([
                    'database.connections.temp_sync' => [
                        'driver' => 'pgsql',
                        'host' => env('DB_HOST', '127.0.0.1'),
                        'port' => env('DB_PORT', '5433'),
                        'database' => $tenantDbName,
                        'username' => env('DB_USERNAME', 'mauricio'),
                        'password' => env('DB_PASSWORD', ''),
                        'charset' => 'utf8',
                        'prefix' => '',
                        'prefix_indexes' => true,
                        'schema' => 'public',
                        'sslmode' => 'prefer',
                    ],
                ]);

                // Check if tenant_features table exists
                $tableExists = DB::connection('temp_sync')
                    ->select("SELECT to_regclass('public.tenant_features') as exists");

                if (! $tableExists[0]->exists) {
                    $this->error("\ntenant_features table does not exist for tenant {$tenant->id}. Skipping...");
                    $errorCount++;
                    $bar->advance();

                    continue;
                }

                // Insert or update panic_button feature
                DB::connection('temp_sync')
                    ->table('tenant_features')
                    ->updateOrInsert(
                        ['tenant_id' => $tenant->id, 'feature' => 'panic_button'],
                        [
                            'enabled' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );

                // Clean up connection
                DB::purge('temp_sync');

                $successCount++;
                $bar->advance();

            } catch (\Exception $e) {
                $this->error("\nFailed to sync panic button for tenant {$tenant->id}: {$e->getMessage()}");
                $errorCount++;
                $bar->advance();
            }
        }

        $bar->finish();

        $this->info("\n\nSync completed!");
        $this->info("✅ Successfully synced: {$successCount} tenants");

        if ($errorCount > 0) {
            $this->warn("⚠️  Errors encountered: {$errorCount} tenants");
        }

        return Command::SUCCESS;
    }
}
