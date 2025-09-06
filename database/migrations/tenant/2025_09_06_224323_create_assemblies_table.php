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
        Schema::create('assemblies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained('conjunto_configs')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['ordinary', 'extraordinary'])->default('ordinary');
            $table->enum('status', ['scheduled', 'in_progress', 'closed', 'cancelled'])->default('scheduled');
            $table->datetime('scheduled_at');
            $table->datetime('started_at')->nullable();
            $table->datetime('ended_at')->nullable();
            $table->integer('required_quorum_percentage')->default(50);
            $table->json('documents')->nullable(); // Array of document IDs/paths
            $table->text('meeting_notes')->nullable();
            $table->json('metadata')->nullable(); // Additional configuration data
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['conjunto_config_id', 'status']);
            $table->index(['scheduled_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assemblies');
    }
};
