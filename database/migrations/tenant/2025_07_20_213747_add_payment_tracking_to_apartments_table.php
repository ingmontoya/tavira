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
        Schema::table('apartments', function (Blueprint $table) {
            $table->date('last_payment_date')->nullable();
            $table->decimal('outstanding_balance', 10, 2)->default(0);
            $table->enum('payment_status', ['current', 'overdue_30', 'overdue_60', 'overdue_90', 'overdue_90_plus'])->default('current');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropColumn(['last_payment_date', 'outstanding_balance', 'payment_status']);
        });
    }
};
