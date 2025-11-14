<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetDatabaseSequences extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:reset-sequences
                            {--table= : Specific table to reset (optional)}
                            {--tenant : Run for all tenants}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset PostgreSQL sequences to match the max ID in each table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if ($this->option('tenant')) {
            return $this->handleTenants();
        }

        $this->info('🔧 Resetting PostgreSQL sequences...');
        $this->newLine();

        $table = $this->option('table');

        if ($table) {
            $this->resetTableSequence($table);
        } else {
            $this->resetAllSequences();
        }

        $this->newLine();
        $this->info('✅ Sequences reset successfully!');

        return Command::SUCCESS;
    }

    /**
     * Handle resetting sequences for all tenants
     */
    protected function handleTenants(): int
    {
        if (! class_exists(\Stancl\Tenancy\Facades\Tenancy::class)) {
            $this->error('Tenancy package not installed');

            return Command::FAILURE;
        }

        $tenants = \App\Models\Tenant::all();

        $this->info("🏢 Resetting sequences for {$tenants->count()} tenants...");
        $this->newLine();

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: {$tenant->id}");

            $tenant->run(function () {
                $this->resetAllSequences();
            });
        }

        $this->newLine();
        $this->info('✅ All tenant sequences reset successfully!');

        return Command::SUCCESS;
    }

    /**
     * Reset sequences for all tables
     */
    protected function resetAllSequences(): void
    {
        $tables = $this->getTables();

        $this->withProgressBar($tables, function ($table) {
            $this->resetTableSequence($table);
        });

        $this->newLine();
    }

    /**
     * Reset sequence for a specific table
     */
    protected function resetTableSequence(string $table): void
    {
        try {
            // Get the primary key column name
            $primaryKey = $this->getPrimaryKey($table);

            if (! $primaryKey) {
                $this->warn("⚠️  Table '{$table}' has no primary key, skipping...");

                return;
            }

            // Get the sequence name
            $sequenceName = "{$table}_{$primaryKey}_seq";

            // Check if table has any records
            $maxId = DB::table($table)->max($primaryKey);

            if (! $maxId) {
                // Table is empty, reset to 1
                DB::statement("ALTER SEQUENCE {$sequenceName} RESTART WITH 1");

                return;
            }

            // Get current sequence value
            $currentVal = DB::selectOne("SELECT last_value FROM {$sequenceName}")->last_value;

            // Only reset if sequence is behind the max ID
            if ($currentVal < $maxId) {
                $newVal = $maxId + 1;
                DB::statement("SELECT setval('{$sequenceName}', {$newVal}, false)");

                if (! $this->option('quiet')) {
                    $this->line("   ✓ {$table}: {$currentVal} → {$newVal}");
                }
            }

        } catch (\Exception $e) {
            if (! $this->option('quiet')) {
                $this->warn("   ⚠️  {$table}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Get all tables in the database
     */
    protected function getTables(): array
    {
        $tables = DB::select(
            "SELECT tablename FROM pg_tables WHERE schemaname = 'public' ORDER BY tablename"
        );

        return array_map(fn ($table) => $table->tablename, $tables);
    }

    /**
     * Get the primary key column name for a table
     */
    protected function getPrimaryKey(string $table): ?string
    {
        $result = DB::selectOne('
            SELECT a.attname
            FROM pg_index i
            JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
            WHERE i.indrelid = ?::regclass AND i.indisprimary
        ', [$table]);

        return $result->attname ?? null;
    }
}
