<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Recreate conjunto_configs table for single configuration
        Schema::create('conjunto_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->integer('number_of_towers');
            $table->integer('floors_per_tower');
            $table->integer('apartments_per_floor');
            $table->boolean('is_active')->default(true);
            $table->json('tower_names')->nullable();
            $table->json('configuration_metadata')->nullable();
            $table->timestamps();
        });

        // No default conjunto config is created - users must create their own

        // Add conjunto_config_id back to apartments table (nullable initially)
        Schema::table('apartments', function (Blueprint $table) {
            $table->unsignedBigInteger('conjunto_config_id')->nullable();
            $table->foreign('conjunto_config_id')->references('id')->on('conjunto_configs')->onDelete('cascade');
            $table->unique(['conjunto_config_id', 'tower', 'number']);
            $table->index(['conjunto_config_id', 'tower', 'floor']);
        });

        // Add conjunto_config_id back to apartment_types table (nullable initially)
        Schema::table('apartment_types', function (Blueprint $table) {
            $table->unsignedBigInteger('conjunto_config_id')->nullable();
            $table->foreign('conjunto_config_id')->references('id')->on('conjunto_configs')->onDelete('cascade');
            $table->unique(['conjunto_config_id', 'name']);
        });

        // No existing records to update since we start fresh
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

        Schema::dropIfExists('conjunto_configs');
    }
};
