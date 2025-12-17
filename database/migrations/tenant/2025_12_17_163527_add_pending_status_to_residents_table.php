<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'Pending' status to residents status check constraint for mobile app registration
     */
    public function up(): void
    {
        // Drop the existing constraint
        DB::statement('ALTER TABLE residents DROP CONSTRAINT IF EXISTS residents_status_check');

        // Add new constraint with Pending status
        DB::statement("ALTER TABLE residents ADD CONSTRAINT residents_status_check CHECK (status IN ('Active', 'Inactive', 'Pending'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the constraint with Pending
        DB::statement('ALTER TABLE residents DROP CONSTRAINT IF EXISTS residents_status_check');

        // Restore original constraint (only Active and Inactive)
        DB::statement("ALTER TABLE residents ADD CONSTRAINT residents_status_check CHECK (status IN ('Active', 'Inactive'))");
    }
};
