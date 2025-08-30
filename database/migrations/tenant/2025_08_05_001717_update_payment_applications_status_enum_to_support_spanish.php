<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to use raw SQL to modify enum constraints
        DB::statement('ALTER TABLE payment_applications DROP CONSTRAINT payment_applications_status_check');
        DB::statement("ALTER TABLE payment_applications ADD CONSTRAINT payment_applications_status_check CHECK (status IN ('active', 'reversed', 'activo', 'reversado'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original English-only constraint
        DB::statement('ALTER TABLE payment_applications DROP CONSTRAINT payment_applications_status_check');
        DB::statement("ALTER TABLE payment_applications ADD CONSTRAINT payment_applications_status_check CHECK (status IN ('active', 'reversed'))");
    }
};
