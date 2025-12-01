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
            $table->boolean('is_recurring_paused')->default(false)->after('is_recurring');
            $table->timestamp('recurring_paused_at')->nullable()->after('is_recurring_paused');
            $table->text('recurring_pause_reason')->nullable()->after('recurring_paused_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenance_requests', function (Blueprint $table) {
            $table->dropColumn(['is_recurring_paused', 'recurring_paused_at', 'recurring_pause_reason']);
        });
    }
};
