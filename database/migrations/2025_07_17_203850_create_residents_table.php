<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('residents', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 20);
            $table->string('document_number', 50)->unique();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable();
            $table->string('mobile_phone', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['M', 'F', 'Other'])->nullable();
            $table->text('emergency_contact')->nullable();
            $table->string('apartment_number', 20);
            $table->string('tower', 50)->nullable();
            $table->enum('resident_type', ['Owner', 'Tenant', 'Family']);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('documents')->nullable();
            $table->timestamps();

            $table->index(['apartment_number', 'tower']);
            $table->index(['status', 'resident_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('residents');
    }
};
