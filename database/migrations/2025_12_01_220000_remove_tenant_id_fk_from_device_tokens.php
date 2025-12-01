<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove the foreign key constraint on tenant_id to allow:
     * 1. Police/security users who are central users without tenant association
     * 2. Device tokens with subdomain strings instead of UUIDs
     *
     * The tenant_id column remains for associating tokens with tenants when applicable,
     * but without the FK constraint for flexibility.
     */
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            // Re-add the foreign key (though this shouldn't be done in practice)
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();
        });
    }
};
