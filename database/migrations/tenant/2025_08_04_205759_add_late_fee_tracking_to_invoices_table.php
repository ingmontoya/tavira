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
            $table->date('last_late_fee_calculation_date')->nullable()->after('last_payment_date');
            $table->integer('late_fee_months_applied')->default(0)->after('last_late_fee_calculation_date');
            $table->json('late_fee_history')->nullable()->after('late_fee_months_applied');
            $table->decimal('original_base_amount', 10, 2)->nullable()->after('late_fee_history');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'last_late_fee_calculation_date',
                'late_fee_months_applied',
                'late_fee_history',
                'original_base_amount',
            ]);
        });
    }
};
