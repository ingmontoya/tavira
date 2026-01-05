<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add user_type column to distinguish between regular User and SecurityPersonnel tokens.
     * This enables proper notification routing for cross-tenant panic alerts.
     */
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            // Add user_type column after user_id
            // Values: 'user' (default), 'security_personnel'
            $table->string('user_type', 50)->default('user')->after('user_id');

            // Add index for efficient querying by user_type
            $table->index(['user_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropIndex(['user_type', 'is_active']);
            $table->dropColumn('user_type');
        });
    }
};
