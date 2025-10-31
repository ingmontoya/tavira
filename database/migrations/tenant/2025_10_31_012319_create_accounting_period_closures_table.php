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
        Schema::create('accounting_period_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained('conjunto_configs')->onDelete('cascade');
            $table->integer('fiscal_year');
            $table->enum('period_type', ['monthly', 'annual'])->default('annual');
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->date('closure_date');
            $table->enum('status', ['draft', 'completed', 'reversed'])->default('draft');
            $table->decimal('total_income', 15, 2)->default(0);
            $table->decimal('total_expenses', 15, 2)->default(0);
            $table->decimal('net_result', 15, 2)->default(0);
            $table->foreignId('closing_transaction_id')->nullable()->constrained('accounting_transactions')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->foreignId('closed_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Índices
            $table->index(['conjunto_config_id', 'fiscal_year', 'period_type'], 'apc_conjunto_year_type_idx');
            $table->unique(['conjunto_config_id', 'fiscal_year', 'period_type'], 'apc_conjunto_year_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_period_closures');
    }
};
