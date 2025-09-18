<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Stancl\Tenancy\Facades\Tenancy;

class FixAssemblyMigrations extends Command
{
    protected $signature = 'assemblies:fix-migrations {--force : Force the operation without confirmation}';

    protected $description = 'Fix assembly migrations inconsistencies across all tenants';

    public function handle()
    {
        if (! $this->option('force') && ! $this->confirm('This will fix assembly migration inconsistencies across all tenants. Continue?')) {
            return 0;
        }

        $this->info('Fixing assembly migrations across all tenants...');

        $tenants = Tenant::all();
        $totalTenants = $tenants->count();
        $fixedTenants = 0;
        $errors = [];

        foreach ($tenants as $tenant) {
            try {
                $this->line("Processing tenant: {$tenant->id}");

                Tenancy::initialize($tenant);

                $fixed = $this->fixTenantMigrations();

                if ($fixed) {
                    $fixedTenants++;
                    $this->info("  ✓ Fixed migrations for tenant: {$tenant->id}");
                } else {
                    $this->line("  - No fixes needed for tenant: {$tenant->id}");
                }

                Tenancy::end();

            } catch (\Exception $e) {
                $errors[] = "Tenant {$tenant->id}: ".$e->getMessage();
                $this->error("  ✗ Error fixing tenant {$tenant->id}: ".$e->getMessage());

                if (Tenancy::getTenant()) {
                    Tenancy::end();
                }
            }
        }

        $this->newLine();
        $this->info('Summary:');
        $this->info("  Total tenants: {$totalTenants}");
        $this->info("  Fixed tenants: {$fixedTenants}");

        if (count($errors) > 0) {
            $this->warn('  Errors: '.count($errors));
            foreach ($errors as $error) {
                $this->error("    - {$error}");
            }
        }

        $this->info("\n✅ Assembly migration fix completed successfully!");

        return 0;
    }

    private function fixTenantMigrations(): bool
    {
        $fixed = false;

        // Check if assemblies table exists but migration isn't recorded
        if (Schema::hasTable('assemblies')) {
            $assemblyMigrationExists = DB::table('migrations')
                ->where('migration', '2025_09_08_155748_create_assemblies_table')
                ->exists();

            if (! $assemblyMigrationExists) {
                $maxBatch = DB::table('migrations')->max('batch') ?: 0;

                DB::table('migrations')->insert([
                    'migration' => '2025_09_08_155748_create_assemblies_table',
                    'batch' => $maxBatch + 1,
                ]);

                $this->line('    - Marked assemblies migration as complete');
                $fixed = true;
            }
        }

        // Check if assembly_attendances table needs to be created
        if (! Schema::hasTable('assembly_attendances')) {
            $attendanceMigrationExists = DB::table('migrations')
                ->where('migration', '2025_09_08_155749_create_assembly_attendances_table')
                ->exists();

            if (! $attendanceMigrationExists) {
                // Run the migration
                Artisan::call('migrate', ['--force' => true]);
                $this->line('    - Created assembly_attendances table');
                $fixed = true;
            }
        } else {
            // Table exists but migration might not be recorded
            $attendanceMigrationExists = DB::table('migrations')
                ->where('migration', '2025_09_08_155749_create_assembly_attendances_table')
                ->exists();

            if (! $attendanceMigrationExists) {
                $maxBatch = DB::table('migrations')->max('batch') ?: 0;

                DB::table('migrations')->insert([
                    'migration' => '2025_09_08_155749_create_assembly_attendances_table',
                    'batch' => $maxBatch + 1,
                ]);

                $this->line('    - Marked assembly_attendances migration as complete');
                $fixed = true;
            }
        }

        return $fixed;
    }
}
