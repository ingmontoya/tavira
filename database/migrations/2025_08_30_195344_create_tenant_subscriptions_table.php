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
        Schema::create('tenant_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable()->index(); // Nullable for new signups
            $table->string('plan_name'); // e.g., BASICO, PROFESIONAL, EMPRESARIAL, ANUAL_BASICO
            $table->decimal('amount', 10, 2);
            $table->string('payment_method'); // wompi_card, wompi_nequi, wompi_pse
            $table->string('payment_reference')->unique();
            $table->string('transaction_id')->unique();
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('payment_data')->nullable(); // Store full Wompi transaction data
            $table->timestamps();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index('payment_reference');
            $table->index('transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenant_subscriptions');
    }
};
