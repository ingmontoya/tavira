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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained('conjunto_configs');
            $table->foreignId('expense_category_id')->constrained('expense_categories');
            $table->string('expense_number')->unique();

            // Vendor information
            $table->string('vendor_name');
            $table->string('vendor_document')->nullable();
            $table->string('vendor_email')->nullable();
            $table->string('vendor_phone')->nullable();

            // Expense details
            $table->string('description');
            $table->date('expense_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);

            // Status and approval
            $table->enum('status', ['borrador', 'pendiente', 'aprobado', 'pagado', 'rechazado', 'cancelado'])->default('borrador');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            // Payment information
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Additional information
            $table->text('notes')->nullable();

            // Accounting mapping
            $table->foreignId('debit_account_id')->nullable()->constrained('chart_of_accounts');
            $table->foreignId('credit_account_id')->nullable()->constrained('chart_of_accounts');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['conjunto_config_id', 'status']);
            $table->index(['conjunto_config_id', 'expense_date']);
            $table->index(['expense_category_id']);
            $table->index(['status', 'due_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
