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
        Schema::create('quotation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->date('deadline')->nullable();
            $table->enum('status', ['draft', 'published', 'closed', 'cancelled'])->default('draft');
            $table->json('attachments')->nullable(); // Store file paths or URLs
            $table->text('requirements')->nullable(); // Additional requirements or specifications
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Pivot table for quotation request categories
        Schema::create('quotation_request_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_request_id')->constrained('quotation_requests')->onDelete('cascade');
            $table->foreignId('provider_category_id')->constrained('provider_categories')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['quotation_request_id', 'provider_category_id'], 'qr_cat_unique');
        });

        // Table for provider responses to quotation requests
        Schema::create('quotation_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_request_id')->constrained('quotation_requests')->onDelete('cascade');
            $table->foreignId('provider_id')->constrained('providers')->onDelete('cascade');
            $table->decimal('quoted_amount', 15, 2);
            $table->text('proposal')->nullable();
            $table->json('attachments')->nullable();
            $table->integer('estimated_days')->nullable();
            $table->enum('status', ['pending', 'accepted', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint to prevent duplicate responses from same provider
            $table->unique(['quotation_request_id', 'provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quotation_responses');
        Schema::dropIfExists('quotation_request_category');
        Schema::dropIfExists('quotation_requests');
    }
};
