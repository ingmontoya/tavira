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
        Schema::create('payment_agreement_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_agreement_id')->constrained()->onDelete('cascade');
            $table->integer('installment_number');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->enum('status', ['pending', 'paid', 'overdue', 'partial'])->default('pending');
            $table->decimal('paid_amount', 10, 2)->default(0.00);
            $table->decimal('penalty_amount', 10, 2)->default(0.00);
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['payment_agreement_id', 'installment_number']);
            $table->index(['payment_agreement_id', 'status']);
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_agreement_installments');
    }
};
