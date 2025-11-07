<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla principal de cuotas extraordinarias
        Schema::create('extraordinary_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conjunto_config_id')->constrained()->onDelete('cascade');

            // Información del proyecto
            $table->string('name'); // ej: "Reparación Transformador 2025"
            $table->text('description'); // Descripción detallada del objetivo
            $table->decimal('total_amount', 12, 2); // Monto total del proyecto

            // Configuración de pago
            $table->integer('number_of_installments'); // Número de cuotas (meses)
            $table->date('start_date'); // Fecha de inicio del cobro
            $table->date('end_date')->nullable(); // Fecha calculada de finalización

            // Distribución del costo
            $table->enum('distribution_type', ['equal', 'by_coefficient'])->default('by_coefficient');
            // equal: monto igual para todos
            // by_coefficient: proporcional al coeficiente de copropiedad

            // Estado
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            // draft: creada pero no activa
            // active: en proceso de cobro
            // completed: todas las cuotas cobradas
            // cancelled: cancelada

            // Tracking de recaudación
            $table->decimal('total_collected', 12, 2)->default(0);
            $table->decimal('total_pending', 12, 2)->default(0);
            $table->integer('installments_generated')->default(0); // Número de cuotas ya generadas

            // Metadata
            $table->json('metadata')->nullable(); // Información adicional
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['conjunto_config_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });

        // Tabla de tracking por apartamento
        Schema::create('extraordinary_assessment_apartments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('extraordinary_assessment_id')->constrained('extraordinary_assessments')->onDelete('cascade');
            $table->foreignId('apartment_id')->constrained('apartments')->onDelete('cascade');

            // Montos asignados
            $table->decimal('total_amount', 10, 2); // Monto total asignado a este apartamento
            $table->decimal('installment_amount', 10, 2); // Monto por cuota

            // Tracking de pago
            $table->integer('installments_paid')->default(0); // Cuántas cuotas ha pagado
            $table->decimal('amount_paid', 10, 2)->default(0); // Cuánto ha pagado en total
            $table->decimal('amount_pending', 10, 2)->default(0); // Cuánto le falta

            // Estado
            $table->enum('status', ['pending', 'in_progress', 'completed', 'overdue'])->default('pending');

            // Fechas
            $table->date('first_payment_date')->nullable();
            $table->date('last_payment_date')->nullable();

            $table->timestamps();

            $table->unique(['extraordinary_assessment_id', 'apartment_id'], 'extraordinary_apt_unique');
            $table->index(['apartment_id', 'status'], 'extraordinary_apt_status_idx');
        });

        // Agregar tipo 'extraordinary' a la tabla de invoices (PostgreSQL syntax)
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT invoices_type_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_type_check CHECK (type IN ('monthly', 'individual', 'late_fee', 'extraordinary'))");

        // Agregar tipo 'extraordinary_assessment' a payment_concepts (PostgreSQL syntax)
        DB::statement('ALTER TABLE payment_concepts DROP CONSTRAINT payment_concepts_type_check');
        DB::statement("ALTER TABLE payment_concepts ADD CONSTRAINT payment_concepts_type_check CHECK (type IN ('common_expense', 'sanction', 'parking', 'special', 'late_fee', 'extraordinary_assessment', 'monthly_administration', 'other'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir cambios en payment_concepts (PostgreSQL syntax)
        DB::statement('ALTER TABLE payment_concepts DROP CONSTRAINT payment_concepts_type_check');
        DB::statement("ALTER TABLE payment_concepts ADD CONSTRAINT payment_concepts_type_check CHECK (type IN ('common_expense', 'sanction', 'parking', 'special', 'late_fee', 'monthly_administration', 'other'))");

        // Revertir cambios en invoices (PostgreSQL syntax)
        DB::statement('ALTER TABLE invoices DROP CONSTRAINT invoices_type_check');
        DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_type_check CHECK (type IN ('monthly', 'individual', 'late_fee'))");

        Schema::dropIfExists('extraordinary_assessment_apartments');
        Schema::dropIfExists('extraordinary_assessments');
    }
};
