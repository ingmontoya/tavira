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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maintenance_request_id')->constrained('maintenance_requests')->onDelete('cascade');
            $table->foreignId('assigned_staff_id')->constrained('maintenance_staff')->onDelete('restrict');
            $table->string('title');
            $table->text('description');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->datetime('scheduled_start_date')->nullable();
            $table->datetime('scheduled_end_date')->nullable();
            $table->datetime('actual_start_date')->nullable();
            $table->datetime('actual_end_date')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->nullable();
            $table->json('materials_needed')->nullable(); // Array of materials
            $table->json('tools_needed')->nullable(); // Array of tools
            $table->text('completion_notes')->nullable();
            $table->integer('quality_rating')->nullable(); // 1-5 rating
            $table->timestamps();

            $table->index(['maintenance_request_id', 'status']);
            $table->index(['assigned_staff_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
