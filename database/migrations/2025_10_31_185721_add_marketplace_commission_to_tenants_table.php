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
        Schema::table('tenants', function (Blueprint $table) {
            $table->decimal('marketplace_commission', 5, 4)
                ->default(0.08)
                ->after('subscription_plan')
                ->comment('Marketplace commission rate (0-1, e.g., 0.08 = 8%)');

            $table->json('feature_usage')
                ->nullable()
                ->after('marketplace_commission')
                ->comment('Track feature usage for limits enforcement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['marketplace_commission', 'feature_usage']);
        });
    }
};
