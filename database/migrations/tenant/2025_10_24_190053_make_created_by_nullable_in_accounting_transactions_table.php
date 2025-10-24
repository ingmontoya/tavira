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
        Schema::table('accounting_transactions', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['created_by']);
        });

        Schema::table('accounting_transactions', function (Blueprint $table) {
            // Make the column nullable
            $table->unsignedBigInteger('created_by')->nullable()->change();
        });

        Schema::table('accounting_transactions', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_transactions', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['created_by']);
        });

        Schema::table('accounting_transactions', function (Blueprint $table) {
            // Make the column not nullable again
            $table->unsignedBigInteger('created_by')->nullable(false)->change();
        });

        Schema::table('accounting_transactions', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });
    }
};
