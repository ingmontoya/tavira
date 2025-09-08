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
        Schema::create('apartment_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vote_id')->constrained('votes')->cascadeOnDelete();
            $table->foreignId('apartment_id')->constrained('apartments')->cascadeOnDelete();
            $table->foreignId('vote_option_id')->nullable()->constrained('vote_options')->cascadeOnDelete();
            $table->decimal('quantitative_value', 15, 2)->nullable(); // For quantitative votes
            $table->enum('choice', ['yes', 'no', 'abstain'])->nullable(); // For yes/no votes
            $table->text('encrypted_vote')->nullable(); // Encrypted vote data for security
            $table->decimal('weight', 8, 4)->default(1.0000); // Voting weight (e.g., based on copropiedad coefficient)
            $table->foreignId('cast_by_user_id')->constrained('users')->cascadeOnDelete(); // User who cast the vote
            $table->foreignId('on_behalf_of_user_id')->nullable()->constrained('users')->cascadeOnDelete(); // If vote was cast by delegate
            $table->timestamp('cast_at');
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Ensure one vote per apartment per vote
            $table->unique(['vote_id', 'apartment_id']);
            $table->index(['vote_id', 'cast_at']);
            $table->index(['apartment_id', 'cast_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('apartment_votes');
    }
};
