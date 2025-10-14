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
            $table->enum('type', ['peticion', 'queja', 'reclamo', 'sugerencia'])->default('peticion');
            $table->string('subject');
            $table->text('description');

            // Submitter information (can be anonymous or authenticated)
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('submitter_name')->nullable();
            $table->string('submitter_email')->nullable();
            $table->string('submitter_phone')->nullable();
            $table->foreignId('apartment_id')->nullable()->constrained()->onDelete('set null');

            // Status tracking
            $table->enum('status', ['pendiente', 'en_revision', 'en_proceso', 'resuelta', 'cerrada'])->default('pendiente');
            $table->enum('priority', ['baja', 'media', 'alta', 'urgente'])->default('media');

            // Assignment and response
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->text('admin_response')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();

            // Tracking
            $table->string('ticket_number')->unique();
            $table->boolean('is_public')->default(true); // If false, only visible to admin and submitter

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('ticket_number');
            $table->index('status');
            $table->index('type');
            $table->index('created_at');
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
