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
        Schema::create('payment_concepts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['common_expense', 'sanction', 'parking', 'special', 'late_fee', 'other']);
            $table->decimal('default_amount', 10, 2)->default(0);
            $table->boolean('is_recurring')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('billing_cycle', ['monthly', 'quarterly', 'annually', 'one_time'])->default('monthly');
            $table->json('applicable_apartment_types')->nullable(); // null = all types
            $table->timestamps();

            $table->index(['conjunto_config_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_concepts');
    }
};
