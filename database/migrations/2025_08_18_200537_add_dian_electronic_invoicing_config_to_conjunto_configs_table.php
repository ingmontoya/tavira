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
            // DIAN Electronic Invoicing Configuration
            $table->boolean('dian_electronic_invoicing_enabled')->default(false);
            $table->json('dian_numbering_ranges')->nullable(); // Rangos de numeración
            $table->json('dian_municipalities')->nullable(); // Municipios
            $table->json('dian_taxes')->nullable(); // Tributos
            $table->json('dian_measurement_units')->nullable(); // Unidades de medida
            $table->json('dian_company_info')->nullable(); // Información de la empresa (NIT, razón social, etc.)
            $table->json('dian_technical_config')->nullable(); // Configuración técnica (certificados, URLs, etc.)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conjunto_configs', function (Blueprint $table) {
            $table->dropColumn([
                'dian_electronic_invoicing_enabled',
                'dian_numbering_ranges',
                'dian_municipalities',
                'dian_taxes',
                'dian_measurement_units',
                'dian_company_info',
                'dian_technical_config',
            ]);
        });
    }
};
