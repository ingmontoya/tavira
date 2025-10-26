<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This migration removes assembly and voting migration records from the central migrations table
     * because these are tenant-scoped tables, not central tables.
     */
    public function up(): void
    {
        // Drop voting and assembly tables from central database in correct order (respecting foreign keys)
        // These tables should only exist in tenant databases
        Schema::dropIfExists('vote_delegates');
        Schema::dropIfExists('apartment_votes');
        Schema::dropIfExists('vote_options');
        Schema::dropIfExists('votes');
        Schema::dropIfExists('assembly_attendances');
        Schema::dropIfExists('assemblies');

        // Remove migration records from central database
        DB::table('migrations')
            ->whereIn('migration', [
                '2025_09_08_155748_create_assemblies_table',
                '2025_09_08_155749_create_assembly_attendances_table',
                '2025_09_06_224327_create_votes_table',
                '2025_09_06_224331_create_vote_options_table',
                '2025_09_06_224335_create_apartment_votes_table',
                '2025_09_06_224338_create_vote_delegates_table',
            ])
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // We don't need to restore these records as they were incorrectly placed
        // If needed, they will be recreated in tenant databases via tenants:migrate
    }
};
