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
        Schema::table('invoices', function (Blueprint $table) {
            // Rename balance_due to balance_amount for consistency
            $table->renameColumn('balance_due', 'balance_amount');

            // Update status enum to include new values
            $table->dropColumn('status');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->enum('status', ['pending', 'partial_payment', 'paid', 'overdue', 'cancelled'])->default('pending')->after('balance_amount');
            $table->date('last_payment_date')->nullable()->after('due_date');
            $table->index(['status', 'due_date']);
            $table->index(['apartment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['last_payment_date', 'status']);
            $table->dropIndex(['status', 'due_date']);
            $table->dropIndex(['apartment_id', 'status']);
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->renameColumn('balance_amount', 'balance_due');
            $table->enum('status', ['pending', 'partial', 'paid', 'overdue', 'cancelled'])->default('pending');
        });
    }
};
