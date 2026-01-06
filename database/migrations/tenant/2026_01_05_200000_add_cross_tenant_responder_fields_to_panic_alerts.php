<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add fields to support cross-tenant responders (SecurityPersonnel/police).
     * These store responder info when the responder is from central database
     * rather than the tenant's users table.
     */
    public function up(): void
    {
        Schema::table('panic_alerts', function (Blueprint $table) {
            // Store responder name for cross-tenant responders (police/security)
            $table->string('responder_name')->nullable()->after('response_type');
            $table->decimal('responder_latitude', 10, 7)->nullable()->after('responder_name');
            $table->decimal('responder_longitude', 10, 7)->nullable()->after('responder_latitude');

            // Store resolver name for cross-tenant resolvers
            $table->string('resolver_name')->nullable()->after('resolved_at');
        });

        // Need to modify responded_by to accept string (UUID) values
        // Change from unsignedBigInteger to string to support SecurityPersonnel UUIDs
        Schema::table('panic_alerts', function (Blueprint $table) {
            // Drop the foreign key constraint first
            if (Schema::hasColumn('panic_alerts', 'responded_by')) {
                try {
                    $table->dropForeign(['responded_by']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
            }

            if (Schema::hasColumn('panic_alerts', 'resolved_by')) {
                try {
                    $table->dropForeign(['resolved_by']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
            }
        });

        // Change column types to string to support UUIDs
        \DB::statement('ALTER TABLE panic_alerts ALTER COLUMN responded_by TYPE VARCHAR(36)');
        \DB::statement('ALTER TABLE panic_alerts ALTER COLUMN resolved_by TYPE VARCHAR(36)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change back to bigint (will fail if UUIDs exist)
        \DB::statement('ALTER TABLE panic_alerts ALTER COLUMN responded_by TYPE BIGINT USING responded_by::BIGINT');
        \DB::statement('ALTER TABLE panic_alerts ALTER COLUMN resolved_by TYPE BIGINT USING resolved_by::BIGINT');

        Schema::table('panic_alerts', function (Blueprint $table) {
            $table->dropColumn([
                'responder_name',
                'responder_latitude',
                'responder_longitude',
                'resolver_name',
            ]);

            // Re-add foreign keys
            $table->foreign('responded_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('resolved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');
        });
    }
};
