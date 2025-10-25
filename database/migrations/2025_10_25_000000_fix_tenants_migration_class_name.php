<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration fixes the class name conflict between vendor and custom tenants table migration.
     */
    public function up(): void
    {
        // Update the migration record to reflect the new class name
        DB::table('migrations')
            ->where('migration', '2019_09_15_000010_create_tenants_table')
            ->update(['migration' => '2019_09_15_000010_create_tenants_table_custom']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert the migration record to the old class name
        DB::table('migrations')
            ->where('migration', '2019_09_15_000010_create_tenants_table_custom')
            ->update(['migration' => '2019_09_15_000010_create_tenants_table']);
    }
};
