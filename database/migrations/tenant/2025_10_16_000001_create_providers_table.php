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
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('global_provider_id')->nullable(); // Reference to central provider
            $table->string('name');
            $table->string('category')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('Colombia');
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_email')->nullable();
            $table->text('notes')->nullable();
            $table->string('tax_regime')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('created_by')->nullable(); // Tenant-specific field
            $table->timestamps();
            $table->softDeletes();

            // Indexes for better performance
            $table->index('global_provider_id');
            $table->index('name');
            $table->index('category');
            $table->index('is_active');
            $table->index('created_by');

            // Foreign key for created_by (tenant-specific)
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
