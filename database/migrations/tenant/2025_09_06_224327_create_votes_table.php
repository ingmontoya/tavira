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
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_id')->constrained('assemblies')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['yes_no', 'multiple_choice', 'quantitative'])->default('yes_no');
            $table->enum('status', ['draft', 'active', 'closed'])->default('draft');
            $table->datetime('opens_at')->nullable();
            $table->datetime('closes_at')->nullable();
            $table->integer('required_quorum_percentage')->default(50);
            $table->integer('required_approval_percentage')->default(50); // For approval (50% = simple majority, 67% = qualified majority)
            $table->boolean('allows_abstention')->default(true);
            $table->json('metadata')->nullable(); // Configuration specific to vote type
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['assembly_id', 'status']);
            $table->index(['status', 'opens_at', 'closes_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('votes');
    }
};
