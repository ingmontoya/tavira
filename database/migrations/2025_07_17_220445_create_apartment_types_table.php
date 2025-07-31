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
        Schema::create('apartment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., 'Tipo A', 'Tipo B'
            $table->text('description')->nullable();
            $table->decimal('area_sqm', 8, 2); // 86.00, 96.00
            $table->integer('bedrooms');
            $table->integer('bathrooms');
            $table->boolean('has_balcony')->default(false);
            $table->boolean('has_laundry_room')->default(false);
            $table->boolean('has_maid_room')->default(false);
            $table->decimal('coefficient', 8, 6); // Coeficiente de copropiedad
            $table->decimal('administration_fee', 10, 2); // Fee específico por tipo
            $table->json('floor_positions')->nullable(); // [1, 2] positions on each floor
            $table->json('features')->nullable(); // Additional features
            $table->timestamps();

            $table->unique(['conjunto_config_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartment_types');
    }
};
