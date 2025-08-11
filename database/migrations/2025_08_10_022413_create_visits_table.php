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
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('visitor_name');
            $table->string('visitor_document_type')->default('CC');
            $table->string('visitor_document_number');
            $table->string('visitor_phone')->nullable();
            $table->text('visit_reason')->nullable();
            $table->datetime('valid_from');
            $table->datetime('valid_until');
            $table->string('qr_code')->unique();
            $table->enum('status', ['pending', 'active', 'used', 'expired', 'cancelled'])->default('pending');
            $table->datetime('entry_time')->nullable();
            $table->datetime('exit_time')->nullable();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('security_notes')->nullable();
            $table->timestamps();

            $table->index(['apartment_id', 'status']);
            $table->index(['created_by']);
            $table->index(['valid_from', 'valid_until']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visits');
    }
};
