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
        // Create provider_categories table in central database
        Schema::create('provider_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // Create pivot table for provider registration categories
        Schema::create('provider_registration_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_registration_id')->constrained('provider_registrations')->onDelete('cascade');
            $table->foreignId('provider_category_id')->constrained('provider_categories')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['provider_registration_id', 'provider_category_id'], 'pr_cat_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_registration_category');
        Schema::dropIfExists('provider_categories');
    }
};
