<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add geographic location to tenants for proximity-based alert notifications.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Geographic coordinates of the residential complex
            $table->decimal('latitude', 10, 7)->nullable()->after('data');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            // Address information
            $table->string('address')->nullable()->after('longitude');
            $table->string('city')->nullable()->after('address');
            $table->string('department')->nullable()->after('city'); // State/Province in Colombia

            // Alert notification radius in kilometers
            // Police within this radius will receive panic alerts
            $table->decimal('alert_radius_km', 5, 2)->default(5.00)->after('department');

            // Index for geographic queries
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropIndex(['latitude', 'longitude']);
            $table->dropColumn([
                'latitude',
                'longitude',
                'address',
                'city',
                'department',
                'alert_radius_km',
            ]);
        });
    }
};
