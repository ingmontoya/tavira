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
            $table->enum('electronic_invoicing_preference', ['electronic', 'physical'])
                ->nullable()
                ->after('whatsapp_number')
                ->comment('Preferencia de facturación electrónica cuando el conjunto permite elección');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('residents', function (Blueprint $table) {
            $table->dropColumn('electronic_invoicing_preference');
        });
    }
};
