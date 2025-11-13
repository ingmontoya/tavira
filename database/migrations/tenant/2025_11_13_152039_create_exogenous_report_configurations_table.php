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
        Schema::create('exogenous_report_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained('conjunto_configs')->onDelete('cascade');

            // Configuration for specific fiscal year
            $table->integer('fiscal_year');
            $table->string('report_type', 20); // '1001', '1003', '1005', '1647'

            // Thresholds and limits
            $table->decimal('minimum_reporting_amount', 15, 2)->default(100000000); // $100M default
            $table->boolean('include_amounts_below_threshold')->default(false);

            // Withholding configuration
            $table->json('withholding_rates')->nullable(); // Map of concept codes to withholding rates
            $table->json('concept_codes')->nullable(); // DIAN concept codes for different types of transactions

            // Export configuration
            $table->string('export_format_preference', 10)->default('xml'); // 'xml', 'txt', 'excel'
            $table->boolean('validate_before_export')->default(true);

            // Entity information (reportante)
            $table->string('entity_document_type', 10)->default('NIT');
            $table->string('entity_document_number', 50)->nullable();
            $table->string('entity_verification_digit', 1)->nullable();
            $table->string('entity_name', 200)->nullable();
            $table->string('entity_address', 200)->nullable();
            $table->string('entity_city', 100)->nullable();
            $table->string('entity_country', 100)->default('Colombia');

            // Responsible person information
            $table->string('responsible_name', 200)->nullable();
            $table->string('responsible_id', 50)->nullable();
            $table->string('responsible_position', 100)->nullable();
            $table->string('responsible_email', 100)->nullable();
            $table->string('responsible_phone', 50)->nullable();

            // Additional settings
            $table->boolean('auto_generate_on_period_close')->default(false);
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            // Unique constraint: one configuration per conjunto per year per report type
            $table->unique(['conjunto_config_id', 'fiscal_year', 'report_type'], 'unique_config_per_year_type');

            // Indexes
            $table->index(['conjunto_config_id', 'fiscal_year']);
            $table->index('fiscal_year');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exogenous_report_configurations');
    }
};
