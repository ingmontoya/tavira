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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservable_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('apartment_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled, completed
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->decimal('cost', 10, 2)->default(0.00);
            $table->boolean('payment_required')->default(false);
            $table->string('payment_status')->default('pending'); // pending, paid, refunded
            $table->dateTime('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['reservable_asset_id', 'start_time', 'end_time']);
            $table->index(['user_id', 'status']);
            $table->index(['start_time', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
