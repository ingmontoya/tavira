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
        Schema::create('payment_concept_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_concept_id')->constrained()->onDelete('cascade');
            $table->foreignId('income_account_id')->constrained('chart_of_accounts')->onDelete('cascade');
            $table->foreignId('receivable_account_id')->nullable()->constrained('chart_of_accounts')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payment_concept_id'], 'unique_payment_concept_mapping');
            $table->index(['payment_concept_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_concept_account_mappings');
    }
};
