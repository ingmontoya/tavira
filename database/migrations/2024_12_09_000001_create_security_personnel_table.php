<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This table is in the CENTRAL database (not tenant-specific)
     * Stores security personnel who can respond to panic alerts across multiple conjuntos
     */
    public function up(): void
    {
        Schema::create('security_personnel', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 20);
            $table->string('organization_type'); // policia, empresa_seguridad, bomberos, ambulancia
            $table->string('organization_name')->nullable();
            $table->string('id_number', 50); // Cédula or ID number
            $table->string('password');
            $table->string('status')->default('pending'); // pending, active, suspended, rejected
            $table->boolean('accept_terms')->default(false);
            $table->boolean('accept_location_tracking')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->uuid('verified_by')->nullable(); // Admin who verified
            $table->timestamps();

            // Indexes for common queries
            $table->index('status');
            $table->index('organization_type');
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('security_personnel');
    }
};
