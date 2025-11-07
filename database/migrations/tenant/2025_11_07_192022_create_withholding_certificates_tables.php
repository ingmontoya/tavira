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
        Schema::create('withholding_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained();
            $table->foreignId('provider_id')->constrained();
            $table->integer('year');
            $table->string('certificate_number')->unique();
            $table->decimal('total_base', 15, 2);
            $table->decimal('total_retained', 15, 2);
            $table->timestamp('issued_at');
            $table->foreignId('issued_by')->constrained('users');
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'year']);
        });

        Schema::create('withholding_certificate_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('withholding_certificate_id')->constrained()->onDelete('cascade');
            $table->foreignId('expense_id')->constrained();
            $table->string('retention_concept');
            $table->decimal('base_amount', 15, 2);
            $table->decimal('retention_percentage', 5, 2);
            $table->decimal('retained_amount', 15, 2);
            $table->string('retention_account_code');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withholding_certificate_details');
        Schema::dropIfExists('withholding_certificates');
    }
};
