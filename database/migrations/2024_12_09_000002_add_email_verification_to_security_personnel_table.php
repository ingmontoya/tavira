<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add email verification and admin approval fields to security_personnel table
     */
    public function up(): void
    {
        Schema::table('security_personnel', function (Blueprint $table) {
            // Email verification
            $table->string('email_verification_token')->nullable()->after('status');
            $table->timestamp('email_verified_at')->nullable()->after('email_verification_token');

            // Admin approval (separate from email verification)
            $table->timestamp('admin_approved_at')->nullable()->after('email_verified_at');
            $table->uuid('admin_approved_by')->nullable()->after('admin_approved_at');
            $table->text('rejection_reason')->nullable()->after('admin_approved_by');

            // Index for token lookup
            $table->index('email_verification_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('security_personnel', function (Blueprint $table) {
            $table->dropIndex(['email_verification_token']);
            $table->dropColumn([
                'email_verification_token',
                'email_verified_at',
                'admin_approved_at',
                'admin_approved_by',
                'rejection_reason',
            ]);
        });
    }
};
