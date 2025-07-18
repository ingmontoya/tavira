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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['individual', 'company'])->default('individual')->after('email_verified_at');
            $table->foreignId('conjunto_config_id')->nullable()->constrained()->onDelete('set null')->after('role');
            $table->index(['role', 'conjunto_config_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['conjunto_config_id']);
            $table->dropIndex(['role', 'conjunto_config_id']);
            $table->dropColumn(['role', 'conjunto_config_id']);
        });
    }
};