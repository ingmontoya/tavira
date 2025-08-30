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
        Schema::create('payment_method_account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained('conjunto_configs')->onDelete('cascade');
            $table->enum('payment_method', ['cash', 'bank_transfer', 'check', 'credit_card', 'debit_card', 'online', 'pse', 'other']);
            $table->foreignId('cash_account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['conjunto_config_id', 'payment_method']);
            $table->index(['conjunto_config_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_method_account_mappings');
    }
};
