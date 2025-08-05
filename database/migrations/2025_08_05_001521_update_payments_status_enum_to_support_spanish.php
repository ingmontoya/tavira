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
        // For PostgreSQL, we need to use raw SQL to modify enum constraints
        DB::statement("ALTER TABLE payments DROP CONSTRAINT payments_status_check");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('pending', 'applied', 'partially_applied', 'reversed', 'pendiente', 'aplicado', 'parcialmente_aplicado', 'reversado'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original English-only constraint
        DB::statement("ALTER TABLE payments DROP CONSTRAINT payments_status_check");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('pending', 'applied', 'partially_applied', 'reversed'))");
    }
};
