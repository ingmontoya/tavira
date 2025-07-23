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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
            $table->foreignId('apartment_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->enum('type', ['monthly', 'individual', 'late_fee']);
            $table->date('billing_date');
            $table->date('due_date');
            $table->year('billing_period_year');
            $table->integer('billing_period_month');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('late_fees', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('balance_due', 10, 2)->default(0);
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'cancelled'])->default('pending');
            $table->date('paid_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->text('payment_reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['apartment_id', 'billing_period_year', 'billing_period_month']);
            $table->index(['status', 'due_date']);
            $table->index(['conjunto_config_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
