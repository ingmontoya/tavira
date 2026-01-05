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
        // Check if column is already varchar (migration might have partially run)
        $columnInfo = DB::select("
            SELECT data_type FROM information_schema.columns
            WHERE table_name = 'device_tokens' AND column_name = 'user_id'
        ");

        $isAlreadyString = !empty($columnInfo) &&
            in_array($columnInfo[0]->data_type, ['character varying', 'varchar', 'text']);

        if ($isAlreadyString) {
            // Column already converted, nothing to do
            return;
        }

        // Check if foreign key exists before trying to drop it
        $foreignKeyExists = DB::select("
            SELECT 1 FROM information_schema.table_constraints
            WHERE LOWER(constraint_name) = 'device_tokens_user_id_foreign'
            AND LOWER(table_name) = 'device_tokens'
            AND constraint_type = 'FOREIGN KEY'
        ");

        if (!empty($foreignKeyExists)) {
            Schema::table('device_tokens', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
            });
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
