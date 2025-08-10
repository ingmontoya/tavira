<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The tables should already be renamed by the previous migration
        // Just handle the column rename and foreign key update
        DB::statement('ALTER TABLE announcement_confirmations RENAME COLUMN comunicado_id TO announcement_id;');
        
        // Drop any existing foreign key constraints and recreate them
        DB::statement('ALTER TABLE announcement_confirmations DROP CONSTRAINT IF EXISTS comunicado_confirmations_comunicado_id_foreign;');
        DB::statement('ALTER TABLE announcement_confirmations DROP CONSTRAINT IF EXISTS announcement_confirmations_comunicado_id_foreign;');
        
        // Create new foreign key constraint
        DB::statement('ALTER TABLE announcement_confirmations ADD CONSTRAINT announcement_confirmations_announcement_id_foreign FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new foreign key constraint
        DB::statement('ALTER TABLE announcement_confirmations DROP CONSTRAINT IF EXISTS announcement_confirmations_announcement_id_foreign;');
        
        // Rename column back
        DB::statement('ALTER TABLE announcement_confirmations RENAME COLUMN announcement_id TO comunicado_id;');
        
        // Recreate old foreign key constraint
        DB::statement('ALTER TABLE announcement_confirmations ADD CONSTRAINT announcement_confirmations_comunicado_id_foreign FOREIGN KEY (comunicado_id) REFERENCES announcements(id) ON DELETE CASCADE;');
    }
};
