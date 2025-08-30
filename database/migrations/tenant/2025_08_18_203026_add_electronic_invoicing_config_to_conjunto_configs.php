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
        Schema::table('conjunto_configs', function (Blueprint $table) {
            $table->enum('dian_electronic_invoicing_mode', ['disabled', 'all', 'optional', 'required_amount'])
                ->default('disabled')
                ->after('dian_electronic_invoicing_enabled');
            $table->decimal('dian_electronic_invoicing_min_amount', 10, 2)
                ->nullable()
                ->after('dian_electronic_invoicing_mode')
                ->comment('Monto mínimo para facturación electrónica obligatoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conjunto_configs', function (Blueprint $table) {
            $table->dropColumn(['dian_electronic_invoicing_mode', 'dian_electronic_invoicing_min_amount']);
        });
    }
};
