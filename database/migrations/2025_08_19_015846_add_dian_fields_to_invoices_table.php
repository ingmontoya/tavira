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
            $table->text('dian_observation')->nullable()->after('electronic_invoice_public_url');
            $table->integer('dian_payment_method')->nullable()->after('dian_observation');
            $table->string('dian_currency', 3)->nullable()->after('dian_payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn([
                'dian_observation',
                'dian_payment_method',
                'dian_currency'
            ]);
        });
    }
};
