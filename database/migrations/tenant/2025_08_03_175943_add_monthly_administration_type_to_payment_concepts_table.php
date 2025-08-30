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
        // Modify the enum to include the new 'monthly_administration' type
        DB::statement('ALTER TABLE payment_concepts DROP CONSTRAINT payment_concepts_type_check');
        DB::statement("ALTER TABLE payment_concepts ADD CONSTRAINT payment_concepts_type_check CHECK (type IN ('common_expense', 'sanction', 'parking', 'special', 'late_fee', 'other', 'monthly_administration'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove the 'monthly_administration' type from the enum
        DB::statement('ALTER TABLE payment_concepts DROP CONSTRAINT payment_concepts_type_check');
        DB::statement("ALTER TABLE payment_concepts ADD CONSTRAINT payment_concepts_type_check CHECK (type IN ('common_expense', 'sanction', 'parking', 'special', 'late_fee', 'other'))");
    }
};
