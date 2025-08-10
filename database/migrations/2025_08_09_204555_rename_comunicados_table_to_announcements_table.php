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
        Schema::rename('comunicados', 'announcements');
        Schema::rename('comunicado_confirmations', 'announcement_confirmations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('announcements', 'comunicados');
        Schema::rename('announcement_confirmations', 'comunicado_confirmations');
    }
};
