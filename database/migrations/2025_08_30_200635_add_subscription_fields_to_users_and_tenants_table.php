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
        // Add subscription fields to tenants table
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('subscription_status')->default('pending')->after('data'); // pending, active, expired, cancelled
            $table->string('subscription_plan')->nullable()->after('subscription_status');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_plan');
            $table->timestamp('subscription_renewed_at')->nullable()->after('subscription_expires_at');
            
            // Index for subscription queries
            $table->index(['subscription_status', 'subscription_expires_at']);
        });

        // Update users table to have better subscription tracking
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('requires_subscription')->default(false)->after('tenant_id');
            $table->timestamp('subscription_required_at')->nullable()->after('requires_subscription');
            
            // Index for subscription queries
            $table->index('requires_subscription');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['subscription_status', 'subscription_expires_at']);
            $table->dropColumn([
                'subscription_status',
                'subscription_plan', 
                'subscription_expires_at',
                'subscription_renewed_at'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['requires_subscription']);
            $table->dropColumn([
                'requires_subscription',
                'subscription_required_at'
            ]);
        });
    }
};
