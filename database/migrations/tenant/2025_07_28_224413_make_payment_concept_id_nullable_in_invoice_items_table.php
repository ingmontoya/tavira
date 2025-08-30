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
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['payment_concept_id']);
            $table->foreignId('payment_concept_id')->nullable()->change();
            $table->foreign('payment_concept_id')->references('id')->on('payment_concepts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropForeign(['payment_concept_id']);
            $table->foreignId('payment_concept_id')->nullable(false)->change();
            $table->foreign('payment_concept_id')->references('id')->on('payment_concepts')->onDelete('cascade');
        });
    }
};
