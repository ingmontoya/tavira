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
        Schema::create('conjunto_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->integer('number_of_towers');
            $table->integer('floors_per_tower');
            $table->integer('apartments_per_floor');
            $table->decimal('base_administration_fee', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->json('tower_names')->nullable(); // ['A', 'B', 'C', 'D', 'E']
            $table->json('configuration_metadata')->nullable(); // Additional config data
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conjunto_configs');
    }
};
