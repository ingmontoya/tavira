<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\PaymentAgreement;
use Illuminate\Database\Seeder;

class PaymentAgreementSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conjuntoConfig = ConjuntoConfig::first();

        if (! $conjuntoConfig) {
            $this->command->warn('No conjunto configuration found. Please run ConjuntoConfigSeeder first.');

            return;
        }

        // Get some apartments to create agreements for
        $apartments = Apartment::where('conjunto_config_id', $conjuntoConfig->id)
            ->limit(5)
            ->get();

        if ($apartments->isEmpty()) {
            $this->command->warn('No apartments found. Please seed apartments first.');

            return;
        }

        $agreements = [
            [
                'apartment_id' => $apartments[0]->id,
                'status' => 'active',
                'total_debt_amount' => 2500000,
                'initial_payment' => 250000,
                'monthly_payment' => 250000,
                'installments' => 9,
                'start_date' => now()->subMonths(2),
                'penalty_rate' => 2.5,
                'terms_and_conditions' => $this->getDefaultTerms(),
                'notes' => 'Acuerdo para saldar deuda acumulada de 6 meses.',
                'approved_at' => now()->subMonths(2)->addDays(2),
                'approved_by' => 'Administrador',
                'created_by' => 'Contabilidad',
            ],
            [
                'apartment_id' => $apartments[1]->id,
                'status' => 'pending_approval',
                'total_debt_amount' => 1800000,
                'monthly_payment' => 300000,
                'installments' => 6,
                'start_date' => now()->addDays(15),
                'penalty_rate' => 2.0,
                'terms_and_conditions' => $this->getDefaultTerms(),
                'notes' => 'Propietario solicita acuerdo por dificultades económicas.',
                'created_by' => 'Administración',
            ],
            [
                'apartment_id' => $apartments[2]->id,
                'status' => 'breached',
                'total_debt_amount' => 3200000,
                'initial_payment' => 200000,
                'monthly_payment' => 400000,
                'installments' => 8,
                'start_date' => now()->subMonths(4),
                'penalty_rate' => 3.0,
                'terms_and_conditions' => $this->getDefaultTerms(),
                'notes' => 'Acuerdo incumplido por falta de pago de 2 cuotas consecutivas.',
                'approved_at' => now()->subMonths(4)->addDays(1),
                'approved_by' => 'Administrador',
                'created_by' => 'Cartera',
            ],
            [
                'apartment_id' => $apartments[3]->id,
                'status' => 'completed',
                'total_debt_amount' => 1500000,
                'monthly_payment' => 300000,
                'installments' => 5,
                'start_date' => now()->subMonths(6),
                'penalty_rate' => 2.0,
                'terms_and_conditions' => $this->getDefaultTerms(),
                'notes' => 'Acuerdo completado exitosamente.',
                'approved_at' => now()->subMonths(6)->addDays(1),
                'approved_by' => 'Administrador',
                'created_by' => 'Administración',
            ],
            [
                'apartment_id' => $apartments[4]->id,
                'status' => 'draft',
                'total_debt_amount' => 2100000,
                'monthly_payment' => 350000,
                'installments' => 6,
                'start_date' => now()->addWeeks(2),
                'penalty_rate' => 2.5,
                'terms_and_conditions' => $this->getDefaultTerms(),
                'notes' => 'Acuerdo en proceso de revisión.',
                'created_by' => 'Contabilidad',
            ],
        ];

        foreach ($agreements as $agreementData) {
            $agreementData['conjunto_config_id'] = $conjuntoConfig->id;
            $agreementData['end_date'] = $agreementData['start_date']->copy()->addMonths($agreementData['installments'] - 1);

            $agreement = PaymentAgreement::create($agreementData);

            // Generate installments for approved, active, breached, and completed agreements
            if (in_array($agreement->status, ['active', 'breached', 'completed'])) {
                $this->generateSampleInstallments($agreement);
            }
        }

        $this->command->info('Payment agreements seeded successfully.');
    }

    private function generateSampleInstallments(PaymentAgreement $agreement): void
    {
        $currentDate = $agreement->start_date->copy();

        for ($i = 1; $i <= $agreement->installments; $i++) {
            $installment = $agreement->installments()->create([
                'installment_number' => $i,
                'amount' => $agreement->monthly_payment,
                'due_date' => $currentDate->copy(),
            ]);

            // Simulate payment patterns based on agreement status
            if ($agreement->status === 'completed') {
                // All installments paid
                $installment->markAsPaid($agreement->monthly_payment, 'transferencia', 'REF-'.rand(100000, 999999));
            } elseif ($agreement->status === 'active') {
                // Pay some installments, leave recent ones pending
                if ($i <= ($agreement->installments - 2)) {
                    $installment->markAsPaid($agreement->monthly_payment, 'transferencia', 'REF-'.rand(100000, 999999));
                } elseif ($currentDate->isPast()) {
                    $installment->markAsOverdue();
                }
            } elseif ($agreement->status === 'breached') {
                // Pay first few, then skip some to cause breach
                if ($i <= 2) {
                    $installment->markAsPaid($agreement->monthly_payment, 'transferencia', 'REF-'.rand(100000, 999999));
                } elseif ($i <= 4 && $currentDate->isPast()) {
                    $installment->markAsOverdue();
                }
            }

            $currentDate->addMonth();
        }
    }

    private function getDefaultTerms(): string
    {
        return 'TÉRMINOS Y CONDICIONES DEL ACUERDO DE PAGO

1. OBJETO DEL ACUERDO
El presente acuerdo tiene por objeto establecer un plan de pagos para saldar la deuda pendiente por concepto de cuotas de administración.

2. OBLIGACIONES DEL DEUDOR
- Realizar los pagos puntualmente en las fechas establecidas
- Mantener al día las cuotas corrientes de administración
- Notificar cualquier dificultad de pago con anticipación

3. PENALIZACIONES POR INCUMPLIMIENTO
- El incumplimiento de dos (2) cuotas consecutivas constituirá causal de terminación del acuerdo
- Se aplicará la tasa de penalización establecida por mora en los pagos
- La terminación del acuerdo por incumplimiento hará exigible la totalidad de la deuda

4. VIGENCIA
El presente acuerdo entrará en vigencia una vez sea aprobado por la administración y tendrá la duración establecida en el cronograma de pagos.

5. ACEPTACIÓN
Las partes declaran conocer y aceptar los términos y condiciones establecidos en el presente acuerdo.';
    }
}
