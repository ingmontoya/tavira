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
        Schema::create('maintenance_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained('conjunto_configs')->onDelete('cascade');
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->json('specialties')->nullable(); // Array of specialties
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->boolean('is_internal')->default(true); // Internal staff vs external contractor
            $table->boolean('is_active')->default(true);
            $table->json('availability_schedule')->nullable(); // Weekly schedule
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_staff');
    }
};
