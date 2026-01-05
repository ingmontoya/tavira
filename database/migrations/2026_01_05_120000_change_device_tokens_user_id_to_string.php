<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Change user_id from bigint to string to support both:
     * - Regular User IDs (bigint, auto-incrementing)
     * - SecurityPersonnel IDs (UUID strings)
     *
     * This enables cross-tenant device token registration for police/security.
     */
    public function up(): void
    {
        // Try to drop the foreign key constraint if it exists
        // Some environments may not have it, so we wrap in try-catch
        try {
            Schema::table('device_tokens', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
        } catch (\Exception $e) {
            // Foreign key doesn't exist, continue
        }

        // Change column type from bigint to string
        // Using raw SQL for PostgreSQL compatibility
        DB::statement('ALTER TABLE device_tokens ALTER COLUMN user_id TYPE VARCHAR(36)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: This is destructive if UUIDs exist in the column
        // They cannot be converted back to bigint
        DB::statement('ALTER TABLE device_tokens ALTER COLUMN user_id TYPE BIGINT USING user_id::BIGINT');

        Schema::table('device_tokens', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
