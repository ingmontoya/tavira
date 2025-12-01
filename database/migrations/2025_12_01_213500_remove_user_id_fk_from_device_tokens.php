<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove the foreign key constraint on user_id because users are stored
     * in tenant databases, not the central database where device_tokens lives.
     */
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            // Re-add the foreign key (though this shouldn't be done in practice)
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
