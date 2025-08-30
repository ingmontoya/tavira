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
        Schema::table('invitations', function (Blueprint $table) {
            $table->boolean('is_mass_invitation')->default(false)->after('message');
            $table->string('mass_invitation_title')->nullable()->after('is_mass_invitation');
            $table->text('mass_invitation_description')->nullable()->after('mass_invitation_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invitations', function (Blueprint $table) {
            $table->dropColumn(['is_mass_invitation', 'mass_invitation_title', 'mass_invitation_description']);
        });
    }
};
