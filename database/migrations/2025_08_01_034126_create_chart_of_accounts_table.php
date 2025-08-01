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
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained('conjunto_configs')->onDelete('cascade');
            $table->string('code', 10)->index();
            $table->string('name', 200);
            $table->text('description')->nullable();
            $table->enum('account_type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->onDelete('cascade');
            $table->tinyInteger('level');
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_third_party')->default(false);
            $table->enum('nature', ['debit', 'credit']);
            $table->boolean('accepts_posting')->default(true);
            $table->timestamps();

            $table->unique(['conjunto_config_id', 'code']);
            $table->index(['conjunto_config_id', 'account_type']);
            $table->index(['conjunto_config_id', 'parent_id']);
            $table->index(['conjunto_config_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
    }
};
