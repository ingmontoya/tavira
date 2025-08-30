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
        Schema::create('reservable_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('common_area'); // common_area, amenity, facility, etc.
            $table->json('availability_rules')->nullable(); // JSON with time slots, days, etc.
            $table->integer('max_reservations_per_user')->default(1);
            $table->integer('reservation_duration_minutes')->default(120);
            $table->integer('advance_booking_days')->default(30);
            $table->decimal('reservation_cost', 10, 2)->default(0.00);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('image_path')->nullable();
            $table->json('metadata')->nullable(); // For extensible configuration
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservable_assets');
    }
};
