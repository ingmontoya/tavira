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
        Schema::table('residents', function (Blueprint $table) {
            $table->string('dian_address')->nullable()->after('electronic_invoicing_preference');
            $table->integer('dian_city_id')->nullable()->after('dian_address');
            $table->integer('dian_legal_organization_type')->nullable()->after('dian_city_id');
            $table->integer('dian_tributary_regime')->nullable()->after('dian_legal_organization_type');
            $table->integer('dian_tributary_liability')->nullable()->after('dian_tributary_regime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn([
                'dian_address',
                'dian_city_id',
                'dian_legal_organization_type',
                'dian_tributary_regime',
                'dian_tributary_liability',
            ]);
        });
    }
};
