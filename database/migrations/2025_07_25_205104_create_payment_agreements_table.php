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
        Schema::create('payment_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
            $table->foreignId('apartment_id')->constrained()->onDelete('cascade');
            $table->string('agreement_number')->unique();
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'active', 'breached', 'completed', 'cancelled'])->default('draft');
            $table->decimal('total_debt_amount', 10, 2);
            $table->decimal('initial_payment', 10, 2)->nullable();
            $table->decimal('monthly_payment', 10, 2);
            $table->integer('installments');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('penalty_rate', 5, 2)->default(0.00);
            $table->text('terms_and_conditions');
            $table->text('notes')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->string('approved_by')->nullable();
            $table->string('created_by');
            $table->timestamps();

            $table->index(['apartment_id', 'status']);
            $table->index(['conjunto_config_id', 'status']);
            $table->index(['status', 'start_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_agreements');
    }
};
