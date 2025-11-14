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
        Schema::create('exogenous_report_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exogenous_report_id')->constrained('exogenous_reports')->onDelete('cascade');

            // Third party information (Provider/Supplier)
            $table->foreignId('provider_id')->nullable()->constrained('providers')->onDelete('set null');
            $table->string('third_party_document_type', 10); // NIT, CC, CE, etc.
            $table->string('third_party_document_number', 50);
            $table->string('third_party_verification_digit', 1)->nullable();
            $table->string('third_party_name', 200);
            $table->string('third_party_address', 200)->nullable();
            $table->string('third_party_city', 100)->nullable();
            $table->string('third_party_country', 100)->default('Colombia');

            // Transaction information
            $table->string('concept_code', 10)->nullable(); // DIAN concept code
            $table->string('concept_name', 200);
            $table->decimal('payment_amount', 15, 2)->default(0); // Total paid
            $table->decimal('withholding_amount', 15, 2)->default(0); // Retención aplicada
            $table->decimal('tax_base', 15, 2)->default(0); // Base gravable
            $table->decimal('withholding_rate', 5, 2)->nullable(); // Tarifa de retención (%)

            // Source references
            $table->string('source_type', 50)->nullable(); // 'expense', 'invoice', 'payment'
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('transaction_number', 50)->nullable();
            $table->date('transaction_date')->nullable();

            // Account classification
            $table->string('account_code', 10)->nullable(); // From chart of accounts
            $table->string('account_name', 200)->nullable();

            // Metadata
            $table->json('metadata')->nullable(); // For storing additional fields per format type

            $table->timestamps();

            // Indexes for performance and lookups
            $table->index(['exogenous_report_id', 'provider_id']);
            $table->index(['exogenous_report_id', 'third_party_document_number']);
            $table->index('provider_id');
            $table->index('third_party_document_number');
            $table->index('source_type');
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exogenous_report_items');
    }
};
