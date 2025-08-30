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
        Schema::create('budget_executions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_item_id')->constrained('budget_items')->onDelete('cascade');
            $table->tinyInteger('period_month');
            $table->year('period_year');
            $table->decimal('budgeted_amount', 15, 2);
            $table->decimal('actual_amount', 15, 2)->default(0);
            $table->decimal('variance_amount', 15, 2)->default(0);
            $table->decimal('variance_percentage', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['budget_item_id', 'period_month', 'period_year']);
            $table->index(['period_year', 'period_month']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_executions');
    }
};
