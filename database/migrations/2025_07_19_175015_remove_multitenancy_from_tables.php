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
        // Remove conjunto_config_id from apartments table
        Schema::table('apartments', function (Blueprint $table) {
            $table->dropForeign(['conjunto_config_id']);
            $table->dropIndex('apartments_conjunto_config_id_tower_number_unique');
            $table->dropIndex('apartments_conjunto_config_id_tower_floor_index');
            $table->dropColumn('conjunto_config_id');
        });

        // Remove conjunto_config_id from apartment_types table
        Schema::table('apartment_types', function (Blueprint $table) {
            $table->dropForeign(['conjunto_config_id']);
            $table->dropUnique(['conjunto_config_id', 'name']);
            $table->dropColumn('conjunto_config_id');
        });

        // Remove conjunto_config_id from users table
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['conjunto_config_id']);
            $table->dropIndex(['role', 'conjunto_config_id']);
            $table->dropColumn(['role', 'conjunto_config_id']);
        });

        // Drop conjunto_configs table entirely
        Schema::dropIfExists('conjunto_configs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate conjunto_configs table
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

        // Add back conjunto_config_id to apartments table
        Schema::table('apartments', function (Blueprint $table) {
            $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
        });

        // Add back conjunto_config_id to apartment_types table
        Schema::table('apartment_types', function (Blueprint $table) {
            $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
            $table->unique(['conjunto_config_id', 'name']);
        });

        // Add back columns to users table
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['company', 'individual'])->default('individual');
            $table->foreignId('conjunto_config_id')->nullable()->constrained()->onDelete('set null');
        });
    }
};
