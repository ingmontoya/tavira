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
        // Drop the old enum constraint and create a new one with Spanish status values
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT invoices_status_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status IN ('pending', 'partial', 'paid', 'overdue', 'cancelled', 'pendiente', 'pago_parcial', 'pagado', 'vencido', 'cancelado'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the original enum constraint
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT invoices_status_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (status IN ('pending', 'partial', 'paid', 'overdue', 'cancelled'))");
    }
};
