<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Device tokens are stored centrally to allow cross-tenant notifications.
     * This enables police officers to receive alerts from nearby residential complexes.
     */
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // User reference (central users table)
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // Tenant reference (which conjunto this user belongs to)
            $table->string('tenant_id')->nullable();
            $table->foreign('tenant_id')->references('id')->on('tenants')->nullOnDelete();

            // Device token from FCM
            $table->string('token', 512)->unique();
            $table->enum('platform', ['ios', 'android', 'web'])->default('android');
            $table->string('device_name')->nullable();

            // Status
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();

            // User's last known location (for proximity-based notifications)
            $table->decimal('last_latitude', 10, 7)->nullable();
            $table->decimal('last_longitude', 10, 7)->nullable();
            $table->timestamp('location_updated_at')->nullable();

            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['user_id', 'is_active']);
            $table->index(['tenant_id', 'is_active']);
            $table->index(['platform', 'is_active']);
            $table->index(['last_latitude', 'last_longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
