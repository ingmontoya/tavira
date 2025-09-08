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
        Schema::create('assembly_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['present', 'absent', 'delegated'])->default('present');
            $table->timestamp('registered_at')->useCurrent();
            $table->foreignId('registered_by')->nullable()->constrained('users');
            $table->json('metadata')->nullable(); // For additional info like online status, etc.
            $table->timestamps();
            
            // Ensure one attendance record per user per assembly
            $table->unique(['assembly_id', 'user_id']);
            
            // Index for quick queries
            $table->index(['assembly_id', 'status']);
            $table->index(['assembly_id', 'apartment_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assembly_attendances');
    }
};
