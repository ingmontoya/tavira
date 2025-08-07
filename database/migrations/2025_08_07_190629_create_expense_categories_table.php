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
        Schema::create('expense_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained('conjunto_configs');
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('default_debit_account_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('default_credit_account_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('budget_account_id')->nullable()->constrained('chart_of_accounts');
            $table->boolean('is_active')->default(true);
            $table->string('color', 7)->nullable(); // Hex color code
            $table->string('icon', 50)->nullable(); // Heroicon name
            $table->boolean('requires_approval')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['conjunto_config_id', 'is_active']);
            $table->unique(['conjunto_config_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expense_categories');
    }
};
