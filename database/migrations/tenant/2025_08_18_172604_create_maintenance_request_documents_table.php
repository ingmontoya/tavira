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
        Schema::create('maintenance_request_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained('maintenance_requests')->onDelete('cascade');
            $table->foreignId('uploaded_by_user_id')->constrained('users')->onDelete('restrict');
            $table->string('name'); // Original filename
            $table->string('file_path'); // Storage path
            $table->string('file_type'); // MIME type
            $table->bigInteger('file_size'); // Size in bytes
            $table->enum('stage', [
                'initial_request', 'evaluation', 'budgeting', 'approval',
                'execution', 'completion', 'evidence', 'warranty',
            ]); // Stage/phase of the maintenance request
            $table->enum('document_type', [
                'photo', 'quote', 'invoice', 'receipt', 'report',
                'specification', 'permit', 'warranty', 'other',
            ]); // Type of document
            $table->text('description')->nullable(); // Description of the document
            $table->json('metadata')->nullable(); // Additional metadata like GPS location, camera info, etc.
            $table->boolean('is_evidence')->default(false); // Mark as evidence/proof
            $table->boolean('is_required_approval')->default(false); // Required for approval process
            $table->timestamps();

            $table->index(['maintenance_request_id', 'stage']);
            $table->index(['maintenance_request_id', 'document_type']);
            $table->index(['stage', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_request_documents');
    }
};
