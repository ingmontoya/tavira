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
        Schema::table('accounting_period_closures', function (Blueprint $table) {
            // Eliminar índices antiguos
            $table->dropUnique('apc_conjunto_year_type_unique');
            $table->dropIndex('apc_conjunto_year_type_idx');

            // Crear nuevos índices
            $table->index(['conjunto_config_id', 'fiscal_year', 'period_type', 'status'], 'apc_conjunto_year_type_status_idx');
            // Para cierres mensuales, permitir múltiples por año (uno por mes)
            // Para cierres anuales, solo uno por año
            // El índice único incluye period_start_date para permitir múltiples meses
            $table->unique(['conjunto_config_id', 'fiscal_year', 'period_type', 'period_start_date', 'status'], 'apc_unique_period_closure');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accounting_period_closures', function (Blueprint $table) {
            // Restaurar índices antiguos
            $table->dropUnique('apc_unique_period_closure');
            $table->dropIndex('apc_conjunto_year_type_status_idx');

            $table->index(['conjunto_config_id', 'fiscal_year', 'period_type'], 'apc_conjunto_year_type_idx');
            $table->unique(['conjunto_config_id', 'fiscal_year', 'period_type'], 'apc_conjunto_year_type_unique');
        });
    }
};
