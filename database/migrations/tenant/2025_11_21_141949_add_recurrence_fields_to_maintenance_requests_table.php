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
        Schema::table('maintenance_requests', function (Blueprint $table) {
            // Recurrence configuration
            $table->boolean('is_recurring')->default(false)->after('requires_council_approval');
            $table->enum('recurrence_frequency', ['daily', 'weekly', 'monthly', 'quarterly', 'semi_annual', 'annual'])->nullable()->after('is_recurring');
            $table->integer('recurrence_interval')->default(1)->after('recurrence_frequency'); // e.g., every 2 weeks, every 3 months
            $table->date('recurrence_start_date')->nullable()->after('recurrence_interval');
            $table->date('recurrence_end_date')->nullable()->after('recurrence_start_date');
            $table->date('next_occurrence_date')->nullable()->after('recurrence_end_date');
            $table->integer('days_before_notification')->default(7)->after('next_occurrence_date'); // Days before to notify
            $table->timestamp('last_notified_at')->nullable()->after('days_before_notification');
            $table->json('recurrence_metadata')->nullable()->after('last_notified_at'); // For storing additional recurrence info

            $table->index(['is_recurring', 'next_occurrence_date']);
            $table->index(['next_occurrence_date', 'last_notified_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropIndex(['is_recurring', 'next_occurrence_date']);
            $table->dropIndex(['next_occurrence_date', 'last_notified_at']);

            $table->dropColumn([
                'is_recurring',
                'recurrence_frequency',
                'recurrence_interval',
                'recurrence_start_date',
                'recurrence_end_date',
                'next_occurrence_date',
                'days_before_notification',
                'last_notified_at',
                'recurrence_metadata',
            ]);
        });
    }
};
