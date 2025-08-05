<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the old enum constraint and create a new one with Spanish status values
        DB::statement("ALTER TABLE accounting_transactions DROP CONSTRAINT accounting_transactions_status_check");
        DB::statement("ALTER TABLE accounting_transactions ADD CONSTRAINT accounting_transactions_status_check CHECK (status IN ('draft', 'posted', 'cancelled', 'borrador', 'contabilizado', 'cancelado'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore the original enum constraint
        DB::statement("ALTER TABLE accounting_transactions DROP CONSTRAINT accounting_transactions_status_check");
        DB::statement("ALTER TABLE accounting_transactions ADD CONSTRAINT accounting_transactions_status_check CHECK (status IN ('draft', 'posted', 'cancelled'))");
    }
};
