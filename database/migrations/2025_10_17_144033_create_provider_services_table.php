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
        Schema::create('provider_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2)->nullable(); // Precio estimado o base
            $table->enum('price_type', ['fixed', 'hourly', 'per_unit', 'quote'])->default('quote');
            $table->string('unit')->nullable(); // m2, hora, proyecto, etc
            $table->foreignId('category_id')->nullable()->constrained('provider_categories')->onDelete('set null');
            $table->boolean('is_active')->default(true);
            $table->json('images')->nullable(); // Array de URLs de imágenes
            $table->json('specifications')->nullable(); // Especificaciones técnicas
            $table->text('terms')->nullable(); // Términos y condiciones del servicio
            $table->integer('estimated_delivery_days')->nullable(); // Días estimados de entrega
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['provider_id', 'is_active']);
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_services');
    }
};
