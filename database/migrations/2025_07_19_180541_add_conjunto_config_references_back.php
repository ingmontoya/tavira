<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First create the default conjunto config
        DB::table('conjunto_configs')->insert([
            'name' => 'Conjunto Residencial Vista Hermosa',
            'description' => 'Moderno conjunto residencial ubicado en el norte de Bogotá con excelentes acabados y zonas verdes.',
            'number_of_towers' => 3,
            'floors_per_tower' => 8,
            'apartments_per_floor' => 4,
            'is_active' => true,
            'tower_names' => json_encode(['A', 'B', 'C']),
            'configuration_metadata' => json_encode([
                'address' => 'Carrera 15 #85-23, Bogotá',
                'phone' => '601-234-5678',
                'email' => 'administracion@vistahermosa.com'
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $conjuntoId = DB::table('conjunto_configs')->first()->id;

        // Add conjunto_config_id back to apartments table with default value
        Schema::table('apartments', function (Blueprint $table) use ($conjuntoId) {
            $table->unsignedBigInteger('conjunto_config_id')->default($conjuntoId);
            $table->foreign('conjunto_config_id')->references('id')->on('conjunto_configs')->onDelete('cascade');
            $table->unique(['conjunto_config_id', 'tower', 'number']);
            $table->index(['conjunto_config_id', 'tower', 'floor']);
        });

        // Add conjunto_config_id back to apartment_types table with default value
        Schema::table('apartment_types', function (Blueprint $table) use ($conjuntoId) {
            $table->unsignedBigInteger('conjunto_config_id')->default($conjuntoId);
            $table->foreign('conjunto_config_id')->references('id')->on('conjunto_configs')->onDelete('cascade');
            $table->unique(['conjunto_config_id', 'name']);
        });

        // Update all existing records to reference the conjunto
        DB::table('apartments')->update(['conjunto_config_id' => $conjuntoId]);
        DB::table('apartment_types')->update(['conjunto_config_id' => $conjuntoId]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropForeign(['conjunto_config_id']);
            $table->dropIndex('apartments_conjunto_config_id_tower_number_unique');
            $table->dropIndex('apartments_conjunto_config_id_tower_floor_index');
            $table->dropColumn('conjunto_config_id');
        });

        Schema::table('apartment_types', function (Blueprint $table) {
            $table->dropForeign(['conjunto_config_id']);
            $table->dropUnique(['conjunto_config_id', 'name']);
            $table->dropColumn('conjunto_config_id');
        });
    }
};