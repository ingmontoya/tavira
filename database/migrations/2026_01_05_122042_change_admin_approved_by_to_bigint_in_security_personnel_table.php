<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Changes admin_approved_by from UUID to bigInteger because
     * the central User model uses integer IDs, not UUIDs.
     */
    public function up(): void
    {
        // For PostgreSQL, we need to drop and recreate the column
        // because direct type change from uuid to bigint isn't supported
        Schema::table('security_personnel', function (Blueprint $table) {
            $table->dropColumn('admin_approved_by');
        });

        Schema::table('security_personnel', function (Blueprint $table) {
            $table->unsignedBigInteger('admin_approved_by')->nullable()->after('admin_approved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_personnel', function (Blueprint $table) {
            $table->dropColumn('admin_approved_by');
        });

        Schema::table('security_personnel', function (Blueprint $table) {
            $table->uuid('admin_approved_by')->nullable()->after('admin_approved_at');
        });
    }
};
