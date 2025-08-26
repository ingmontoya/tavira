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
        Schema::create('pqrs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained()->cascadeOnDelete();
            $table->string('ticket_number')->unique();
            $table->enum('type', ['peticion', 'queja', 'reclamo', 'sugerencia']);
            $table->string('subject');
            $table->text('description');
            $table->enum('priority', ['baja', 'media', 'alta', 'urgente'])->default('media');
            $table->enum('status', ['abierto', 'en_proceso', 'resuelto', 'cerrado'])->default('abierto');
            
            // Resident information
            $table->foreignId('apartment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->constrained('users')->cascadeOnDelete();
            $table->string('contact_name');
            $table->string('contact_email');
            $table->string('contact_phone')->nullable();
            
            // Administrative handling
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('resolution')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            
            // Follow-up
            $table->boolean('requires_follow_up')->default(false);
            $table->timestamp('follow_up_date')->nullable();
            $table->text('follow_up_notes')->nullable();
            
            // Rating/Satisfaction
            $table->integer('satisfaction_rating')->nullable(); // 1-5 scale
            $table->text('satisfaction_comments')->nullable();
            $table->timestamp('satisfaction_submitted_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index(['conjunto_config_id', 'status']);
            $table->index(['submitted_by', 'created_at']);
            $table->index(['assigned_to', 'status']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pqrs');
    }
};
