<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotation_responses', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['provider_id']);

            // Keep the column but remove the foreign key constraint
            // The column will still exist and store the provider_id as an integer
            // but without database-level referential integrity
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotation_responses', function (Blueprint $table) {
            // Note: We cannot add the foreign key back because providers table
            // is in the central database, not the tenant database
            // This down migration is here for consistency but won't fully restore
            // the original state
        });
    }
};
