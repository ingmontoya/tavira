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
        Schema::table('announcements', function (Blueprint $table) {
            // Targeting scope: 'general', 'tower', 'apartment_type', 'apartment'
            $table->string('target_scope')->default('general')->after('type');

            // Target specific towers (JSON array for multiple towers)
            $table->json('target_towers')->nullable()->after('target_scope');

            // Target specific apartment types (JSON array for multiple types)
            $table->json('target_apartment_type_ids')->nullable()->after('target_towers');

            // Target specific apartments (JSON array for multiple apartments)
            $table->json('target_apartment_ids')->nullable()->after('target_apartment_type_ids');

            // Add indexes for performance
            $table->index(['target_scope', 'status', 'published_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropIndex(['target_scope', 'status', 'published_at']);
            $table->dropColumn([
                'target_scope',
                'target_towers',
                'target_apartment_type_ids',
                'target_apartment_ids',
            ]);
        });
    }
};
