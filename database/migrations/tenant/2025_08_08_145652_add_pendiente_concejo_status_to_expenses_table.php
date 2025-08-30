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
        // Drop the existing enum constraint and recreate with the new value
        DB::statement('ALTER TABLE expenses DROP CONSTRAINT IF EXISTS expenses_status_check');

        // Add the new constraint with all status values including 'pendiente_concejo'
        DB::statement("ALTER TABLE expenses ADD CONSTRAINT expenses_status_check CHECK (status IN ('borrador', 'pendiente', 'pendiente_concejo', 'aprobado', 'pagado', 'rechazado', 'cancelado'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // PostgreSQL doesn't support removing enum values easily
        // We'll leave the enum value in place for data integrity
        // If needed, this would require recreating the entire enum type
    }
};
