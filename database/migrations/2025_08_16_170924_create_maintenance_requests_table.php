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
        Schema::create('maintenance_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained('conjunto_configs')->onDelete('cascade');
            $table->foreignId('maintenance_category_id')->constrained('maintenance_categories')->onDelete('restrict');
            $table->foreignId('apartment_id')->nullable()->constrained('apartments')->onDelete('set null');
            $table->foreignId('requested_by_user_id')->constrained('users')->onDelete('restrict');
            $table->foreignId('assigned_staff_id')->nullable()->constrained('maintenance_staff')->onDelete('set null');
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('title');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', [
                'created', 'evaluation', 'budgeted', 'pending_approval', 'approved',
                'assigned', 'in_progress', 'completed', 'closed', 'rejected', 'suspended',
            ])->default('created');
            $table->string('location')->nullable(); // e.g., "Common Area", "Pool", etc.
            $table->decimal('estimated_cost', 10, 2)->nullable();
            $table->decimal('actual_cost', 10, 2)->nullable();
            $table->date('estimated_completion_date')->nullable();
            $table->date('actual_completion_date')->nullable();
            $table->boolean('requires_council_approval')->default(false);
            $table->timestamp('council_approved_at')->nullable();
            $table->json('notes')->nullable(); // Array of timestamped notes
            $table->json('attachments')->nullable(); // Array of file paths/URLs
            $table->timestamps();

            $table->index(['conjunto_config_id', 'status']);
            $table->index(['maintenance_category_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_requests');
    }
};
