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
        Schema::create('correspondences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
            $table->string('tracking_number')->unique();
            $table->string('sender_name');
            $table->string('sender_company')->nullable();
            $table->enum('type', ['package', 'letter', 'document', 'other']);
            $table->text('description');
            $table->foreignId('apartment_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['received', 'delivered', 'pending_signature', 'returned'])->default('received');
            $table->foreignId('received_by')->constrained('users');
            $table->timestamp('received_at');
            $table->foreignId('delivered_by')->nullable()->constrained('users');
            $table->timestamp('delivered_at')->nullable();
            $table->text('delivery_notes')->nullable();
            $table->boolean('requires_signature')->default(false);
            $table->string('signature_path')->nullable();
            $table->string('recipient_name')->nullable();
            $table->string('recipient_document')->nullable();
            $table->timestamps();

            $table->index(['conjunto_config_id', 'apartment_id']);
            $table->index(['status', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('correspondences');
    }
};
