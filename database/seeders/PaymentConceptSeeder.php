<?php

namespace Database\Seeders;

use App\Models\PaymentConcept;
use Illuminate\Database\Seeder;

class PaymentConceptSeeder extends Seeder
{
    public function run(): void
    {

        $concepts = [
            [
                'name' => 'Administración Mensual',
                'description' => 'Cuota de administración mensual base por tipo de apartamento',
                'type' => 'monthly_administration',
                'default_amount' => 0, // Será dinámico por apartamento
                'is_recurring' => true,
                'is_active' => true,
                'billing_cycle' => 'monthly',
                'applicable_apartment_types' => null, // Applies to all apartment types
            ],
            [
                'name' => 'Administración - Gastos Adicionales',
                'description' => 'Gastos administrativos adicionales fuera de la cuota base',
                'type' => 'common_expense',
                'default_amount' => 150000,
                'is_recurring' => true,
                'is_active' => true,
                'billing_cycle' => 'monthly',
                'applicable_apartment_types' => null,
            ],
            [
                'name' => 'Mantenimiento de Ascensores',
                'description' => 'Mantenimiento preventivo y correctivo de ascensores',
                'type' => 'common_expense',
                'default_amount' => 25000,
                'is_recurring' => true,
                'is_active' => true,
                'billing_cycle' => 'monthly',
                'applicable_apartment_types' => null,
            ],
            [
                'name' => 'Vigilancia',
                'description' => 'Servicio de vigilancia y portería 24 horas',
                'type' => 'common_expense',
                'default_amount' => 75000,
                'is_recurring' => true,
                'is_active' => true,
                'billing_cycle' => 'monthly',
                'applicable_apartment_types' => null,
            ],
            [
                'name' => 'Limpieza y Aseo',
                'description' => 'Servicio de limpieza de áreas comunes y manejo de residuos',
                'type' => 'common_expense',
                'default_amount' => 35000,
                'is_recurring' => true,
                'is_active' => true,
                'billing_cycle' => 'monthly',
                'applicable_apartment_types' => null,
            ],
            [
                'name' => 'Servicios Públicos Comunes',
                'description' => 'Agua, luz y gas de áreas comunes',
                'type' => 'common_expense',
                'default_amount' => 40000,
                'is_recurring' => true,
                'is_active' => true,
                'billing_cycle' => 'monthly',
                'applicable_apartment_types' => null,
            ],
            [
                'name' => 'Parqueadero Adicional',
                'description' => 'Cuota mensual por parqueadero adicional',
                'type' => 'parking',
                'default_amount' => 50000,
                'is_recurring' => true,
                'is_active' => true,
                'billing_cycle' => 'monthly',
                'applicable_apartment_types' => null,
            ],
            [
                'name' => 'Sanción por Ruido',
                'description' => 'Multa por alteración del orden y ruido excesivo',
                'type' => 'sanction',
                'default_amount' => 100000,
                'is_recurring' => false,
                'is_active' => true,
                'billing_cycle' => 'one_time',
                'applicable_apartment_types' => null,
            ],
            [
                'name' => 'Sanción por Mascotas',
                'description' => 'Multa por incumplimiento de normas de mascotas',
                'type' => 'sanction',
                'default_amount' => 75000,
                'is_recurring' => false,
                'is_active' => true,
                'billing_cycle' => 'one_time',
                'applicable_apartment_types' => null,
            ],
            [
                'name' => 'Interés de Mora',
                'description' => 'Intereses por mora en el pago de obligaciones',
                'type' => 'late_fee',
                'default_amount' => 0,
                'is_recurring' => false,
                'is_active' => true,
                'billing_cycle' => 'one_time',
                'applicable_apartment_types' => null,
            ],
            [
                'name' => 'Cuota Extraordinaria',
                'description' => 'Cuota especial para obras o mejoras del conjunto',
                'type' => 'special',
                'default_amount' => 200000,
                'is_recurring' => false,
                'is_active' => false,
                'billing_cycle' => 'one_time',
                'applicable_apartment_types' => null,
            ],
        ];

        foreach ($concepts as $conceptData) {
            PaymentConcept::firstOrCreate(
                [
                    'name' => $conceptData['name'],
                    'type' => $conceptData['type'],
                ],
                [
                    'description' => $conceptData['description'],
                    'default_amount' => $conceptData['default_amount'],
                    'is_recurring' => $conceptData['is_recurring'],
                    'is_active' => $conceptData['is_active'],
                    'billing_cycle' => $conceptData['billing_cycle'],
                    'applicable_apartment_types' => $conceptData['applicable_apartment_types'],
                ]
            );
        }

        if ($this->command) {
            $this->command->info('Payment concepts seeded successfully.');
        }
    }
}
