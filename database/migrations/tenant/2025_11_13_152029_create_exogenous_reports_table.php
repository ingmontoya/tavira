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
        Schema::create('exogenous_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained('conjunto_configs')->onDelete('cascade');

            // Report identification
            $table->string('report_number', 20)->unique();
            $table->string('report_type', 20); // '1001', '1003', '1005', '1647'
            $table->string('report_name', 200);

            // Period information
            $table->integer('fiscal_year');
            $table->date('period_start');
            $table->date('period_end');

            // Status and processing
            $table->enum('status', ['draft', 'generated', 'validated', 'exported', 'submitted'])->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->timestamp('submitted_at')->nullable();

            // Statistics
            $table->integer('total_items')->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('total_withholding', 15, 2)->default(0);

            // File information
            $table->string('export_file_path')->nullable();
            $table->string('export_format', 10)->nullable(); // 'xml', 'txt', 'excel'

            // Notes and metadata
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // For storing additional configuration or validation results

            // Audit fields
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('validated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('exported_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['conjunto_config_id', 'fiscal_year']);
            $table->index(['conjunto_config_id', 'report_type']);
            $table->index(['conjunto_config_id', 'status']);
            $table->index('fiscal_year');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exogenous_reports');
    }
};
