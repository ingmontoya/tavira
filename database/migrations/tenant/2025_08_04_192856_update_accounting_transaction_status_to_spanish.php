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
        // First, drop the existing enum constraint
        DB::statement('ALTER TABLE accounting_transactions DROP CONSTRAINT accounting_transactions_status_check');

        // Then update existing records to use Spanish values
        DB::table('accounting_transactions')
            ->where('status', 'draft')
            ->update(['status' => 'borrador']);

        DB::table('accounting_transactions')
            ->where('status', 'posted')
            ->update(['status' => 'contabilizado']);

        DB::table('accounting_transactions')
            ->where('status', 'cancelled')
            ->update(['status' => 'cancelado']);

        // Finally, add the new enum constraint with Spanish values
        DB::statement("ALTER TABLE accounting_transactions ADD CONSTRAINT accounting_transactions_status_check CHECK (status::text = ANY (ARRAY['borrador'::character varying, 'contabilizado'::character varying, 'cancelado'::character varying]::text[]))");

        // Update default value
        DB::statement("ALTER TABLE accounting_transactions ALTER COLUMN status SET DEFAULT 'borrador'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First update data back to English
        DB::table('accounting_transactions')
            ->where('status', 'borrador')
            ->update(['status' => 'draft']);

        DB::table('accounting_transactions')
            ->where('status', 'contabilizado')
            ->update(['status' => 'posted']);

        DB::table('accounting_transactions')
            ->where('status', 'cancelado')
            ->update(['status' => 'cancelled']);

        // Then restore English enum
        DB::statement('ALTER TABLE accounting_transactions DROP CONSTRAINT accounting_transactions_status_check');
        DB::statement("ALTER TABLE accounting_transactions ADD CONSTRAINT accounting_transactions_status_check CHECK (status::text = ANY (ARRAY['draft'::character varying, 'posted'::character varying, 'cancelled'::character varying]::text[]))");

        // Restore default value
        DB::statement("ALTER TABLE accounting_transactions ALTER COLUMN status SET DEFAULT 'draft'");
    }
};
