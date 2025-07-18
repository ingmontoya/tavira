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
            // Add foreign key to apartments table
            $table->foreignId('apartment_id')->nullable()->after('emergency_contact')->constrained()->onDelete('set null');
            
            // Remove old apartment_number and tower columns since we now have apartment relationship
            $table->dropIndex(['apartment_number', 'tower']);
            $table->dropColumn(['apartment_number', 'tower']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            // Add back the old columns
            $table->string('apartment_number', 20)->after('emergency_contact');
            $table->string('tower', 50)->nullable()->after('apartment_number');
            $table->index(['apartment_number', 'tower']);
            
            // Remove the foreign key
            $table->dropForeign(['apartment_id']);
            $table->dropColumn('apartment_id');
        });
    }
};