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
        // Ensure required tables exist
        if (!Schema::hasTable('conjunto_configs')) {
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
        }

        if (!Schema::hasTable('apartment_types')) {
            Schema::create('apartment_types', function (Blueprint $table) {
                $table->id();
                $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('area_sqm', 8, 2);
                $table->integer('bedrooms');
                $table->integer('bathrooms');
                $table->boolean('has_balcony')->default(false);
                $table->boolean('has_laundry_room')->default(false);
                $table->boolean('has_maid_room')->default(false);
                $table->decimal('coefficient', 8, 6);
                $table->decimal('administration_fee', 10, 2);
                $table->json('floor_positions')->nullable();
                $table->json('features')->nullable();
                $table->timestamps();
                $table->unique(['conjunto_config_id', 'name']);
            });
        }

        Schema::create('apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
            $table->foreignId('apartment_type_id')->constrained()->onDelete('cascade');
            $table->string('number'); // e.g., '101', '102', '201', '202'
            $table->string('tower'); // e.g., 'A', 'B', 'C'
            $table->integer('floor');
            $table->integer('position_on_floor'); // 1, 2, 3, 4 (position on the floor)
            $table->enum('status', ['Available', 'Occupied', 'Maintenance', 'Reserved'])->default('Available');
            $table->decimal('monthly_fee', 10, 2); // Current administration fee
            $table->json('utilities')->nullable(); // Water, electricity, gas meters
            $table->json('features')->nullable(); // Specific features for this apartment
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['conjunto_config_id', 'tower', 'number']);
            $table->index(['conjunto_config_id', 'tower', 'floor']);
            $table->index(['apartment_type_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartments');
    }
};
