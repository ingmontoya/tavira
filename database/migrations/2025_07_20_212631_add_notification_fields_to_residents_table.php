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
            $table->boolean('email_notifications')->default(true)->after('notes');
            $table->boolean('whatsapp_notifications')->default(false)->after('email_notifications');
            $table->string('whatsapp_number', 20)->nullable()->after('whatsapp_notifications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn(['email_notifications', 'whatsapp_notifications', 'whatsapp_number']);
        });
    }
};
