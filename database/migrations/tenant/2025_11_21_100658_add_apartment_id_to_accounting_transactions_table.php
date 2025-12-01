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
            $table->foreignId('apartment_id')
                ->nullable()
                ->after('reference_id')
                ->constrained('apartments')
                ->onDelete('set null');

            $table->index('apartment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_transactions', function (Blueprint $table) {
            $table->dropForeign(['apartment_id']);
            $table->dropIndex(['apartment_id']);
            $table->dropColumn('apartment_id');
        });
    }
};
