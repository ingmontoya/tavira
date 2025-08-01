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
        Schema::create('budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained('budgets')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->enum('category', ['income', 'expense']);
            $table->decimal('budgeted_amount', 15, 2);
            $table->decimal('jan_amount', 15, 2)->default(0);
            $table->decimal('feb_amount', 15, 2)->default(0);
            $table->decimal('mar_amount', 15, 2)->default(0);
            $table->decimal('apr_amount', 15, 2)->default(0);
            $table->decimal('may_amount', 15, 2)->default(0);
            $table->decimal('jun_amount', 15, 2)->default(0);
            $table->decimal('jul_amount', 15, 2)->default(0);
            $table->decimal('aug_amount', 15, 2)->default(0);
            $table->decimal('sep_amount', 15, 2)->default(0);
            $table->decimal('oct_amount', 15, 2)->default(0);
            $table->decimal('nov_amount', 15, 2)->default(0);
            $table->decimal('dec_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['budget_id', 'account_id']);
            $table->index(['budget_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('budget_items');
    }
};
