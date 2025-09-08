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
        Schema::create('vote_delegates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assembly_id')->constrained('assemblies')->cascadeOnDelete();
            $table->foreignId('delegator_apartment_id')->constrained('apartments')->cascadeOnDelete();
            $table->foreignId('delegate_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('authorized_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'active', 'revoked', 'expired'])->default('pending');
            $table->datetime('authorized_at')->nullable();
            $table->datetime('expires_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Ensure one delegate per apartment per assembly
            $table->unique(['assembly_id', 'delegator_apartment_id']);
            $table->index(['assembly_id', 'delegate_user_id', 'status']);
            $table->index(['delegate_user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vote_delegates');
    }
};
