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
        Schema::create('visit_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visit_id')->constrained()->onDelete('cascade');
            $table->string('guest_name');
            $table->enum('document_type', ['CC', 'CE', 'Pasaporte', 'TI', 'Otro'])->default('CC');
            $table->string('document_number');
            $table->string('phone', 20)->nullable();
            $table->string('vehicle_plate', 10)->nullable();
            $table->string('vehicle_color', 50)->nullable();
            $table->timestamps();

            // Indexes
            $table->index('visit_id');
            $table->index(['document_type', 'document_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visit_guests');
    }
};
