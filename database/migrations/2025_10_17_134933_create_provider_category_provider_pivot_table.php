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
        // Create pivot table for provider categories (many-to-many relationship)
        Schema::create('provider_category_provider', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            $table->foreignId('provider_category_id')->constrained('provider_categories')->onDelete('cascade');
            $table->timestamps();

            // Ensure unique combinations
            $table->unique(['provider_id', 'provider_category_id'], 'provider_category_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_category_provider');
    }
};
