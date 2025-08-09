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
        Schema::create('jelpit_payment_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained('conjunto_configs')->cascadeOnDelete();
            
            // Raw data from Jelpit file
            $table->string('payment_type'); // Tipo de pago
            $table->string('reference_number')->nullable(); // No. Ref
            $table->date('transaction_date'); // Fecha de transacción
            $table->time('transaction_time'); // Hora de transacción  
            $table->decimal('transaction_amount', 12, 2); // Valor transacción
            $table->date('posting_date'); // Fecha de abono
            $table->string('approval_number')->nullable(); // Número de aprobación
            $table->string('pse_cycle')->nullable(); // Ciclo PSE
            $table->string('office_code')->nullable(); // Código de oficina
            $table->string('office_name')->nullable(); // Nombre de oficina
            $table->string('originator_nit')->nullable(); // NIT Originador
            $table->string('reference_2')->nullable(); // Referencia 2
            $table->text('payment_detail')->nullable(); // Detalle del pago
            
            // Reconciliation fields
            $table->string('reconciliation_status')->default('pending'); // pending, matched, manual_review, rejected
            $table->foreignId('apartment_id')->nullable()->constrained('apartments')->nullOnDelete();
            $table->string('match_type')->nullable(); // apartment_number, nit_match, manual
            $table->text('match_notes')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->nullOnDelete(); // Link to created payment
            
            // Processing fields
            $table->string('import_batch_id'); // To group imports from same file
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['conjunto_config_id', 'reconciliation_status']);
            $table->index(['import_batch_id']);
            $table->index(['originator_nit']);
            $table->index(['reference_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jelpit_payment_imports');
    }
};
