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
        Schema::table('expenses', function (Blueprint $table) {
            // Council approval fields
            $table->foreignId('council_approved_by')->nullable()->after('approved_at')->constrained('users')->onDelete('cascade');
            $table->timestamp('council_approved_at')->nullable()->after('council_approved_by');
        });

        // We'll handle the enum value through application logic for now
        // The new status will be validated at the application level
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['council_approved_by']);
            $table->dropColumn(['council_approved_by', 'council_approved_at']);
        });

        // Note: PostgreSQL doesn't allow removing enum values easily
        // This would require recreating the entire type, which is complex
        // For now, we'll leave the enum value in place
    }
};
