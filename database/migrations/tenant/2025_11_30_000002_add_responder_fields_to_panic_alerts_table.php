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
        Schema::table('panic_alerts', function (Blueprint $table) {
            // Who responded to the alert
            $table->unsignedBigInteger('responded_by')->nullable()->after('status');
            $table->timestamp('responded_at')->nullable()->after('responded_by');
            $table->enum('response_type', ['accepted', 'rejected'])->nullable()->after('responded_at');

            // Track who resolved the alert
            $table->unsignedBigInteger('resolved_by')->nullable()->after('response_type');
            $table->timestamp('resolved_at')->nullable()->after('resolved_by');
            $table->text('resolution_notes')->nullable()->after('resolved_at');

            // Foreign keys
            $table->foreign('responded_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->foreign('resolved_by')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            // Index for finding alerts by responder
            $table->index(['responded_by', 'response_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('panic_alerts', function (Blueprint $table) {
            $table->dropForeign(['responded_by']);
            $table->dropForeign(['resolved_by']);
            $table->dropIndex(['responded_by', 'response_type']);

            $table->dropColumn([
                'responded_by',
                'responded_at',
                'response_type',
                'resolved_by',
                'resolved_at',
                'resolution_notes',
            ]);
        });
    }
};
