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
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->boolean('is_puc_standard')->default(false)->after('accepts_posting');
            $table->string('puc_class')->nullable()->after('is_puc_standard')->comment('Clase: Activo, Pasivo, Patrimonio, Ingresos, Gastos, etc.');
            $table->index(['conjunto_config_id', 'is_puc_standard']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('chart_of_accounts', function (Blueprint $table) {
            $table->dropIndex(['conjunto_config_id', 'is_puc_standard']);
            $table->dropColumn(['is_puc_standard', 'puc_class']);
        });
    }
};
