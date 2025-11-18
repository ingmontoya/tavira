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
        Schema::table('providers', function (Blueprint $table) {
            $table->enum('subscription_plan', ['none', 'basico', 'profesional', 'premium'])
                  ->default('none')
                  ->after('is_active');
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_plan');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_started_at');
            $table->boolean('has_seen_pricing')->default(false)->after('subscription_expires_at');
            $table->decimal('commission_rate', 5, 2)->default(11.00)->after('has_seen_pricing');
            $table->integer('leads_remaining')->default(0)->after('commission_rate');
            $table->integer('leads_used_this_month')->default(0)->after('leads_remaining');
            $table->boolean('has_b2b2c_access')->default(false)->after('leads_used_this_month');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn([
                'subscription_plan',
                'subscription_started_at',
                'subscription_expires_at',
                'has_seen_pricing',
                'commission_rate',
                'leads_remaining',
                'leads_used_this_month',
                'has_b2b2c_access',
            ]);
        });
    }
};
