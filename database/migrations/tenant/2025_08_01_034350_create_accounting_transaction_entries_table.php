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
        Schema::create('accounting_transaction_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_transaction_id')->constrained('accounting_transactions')->onDelete('cascade');
            $table->foreignId('account_id')->constrained('chart_of_accounts')->onDelete('restrict');
            $table->text('description');
            $table->decimal('debit_amount', 15, 2)->default(0);
            $table->decimal('credit_amount', 15, 2)->default(0);
            $table->string('third_party_type')->nullable();
            $table->unsignedBigInteger('third_party_id')->nullable();
            $table->unsignedBigInteger('cost_center_id')->nullable();
            $table->timestamps();

            $table->index(['accounting_transaction_id']);
            $table->index(['account_id']);
            $table->index(['third_party_type', 'third_party_id']);
            $table->index(['debit_amount']);
            $table->index(['credit_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounting_transaction_entries');
    }
};
