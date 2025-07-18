<?php

namespace Database\Seeders;

use App\Models\Resident;
use Illuminate\Database\Seeder;

class ResidentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Sample data for different conjuntos
        $conjuntos = [
            'Conjunto Residencial Los Nogales',
            'Torres del Parque',
            'Ciudadela San José',
            'Conjunto Villa María',
            'Residencial El Bosque'
        ];

        // Clear existing residents
        Resident::truncate();

        // Create residents for each conjunto
        foreach ($conjuntos as $conjunto) {
            // Generate residents for different towers
            $towers = ['A', 'B', 'C', 'D'];
            
            foreach ($towers as $tower) {
                // Generate 15-20 residents per tower
                $residentsCount = rand(15, 20);
                
                for ($i = 1; $i <= $residentsCount; $i++) {
                    $apartmentNumber = str_pad($i, 3, '0', STR_PAD_LEFT);
                    
                    // Create primary resident (owner)
                    Resident::factory()
                        ->owner()
                        ->active()
                        ->inApartment($apartmentNumber, $tower)
                        ->create([
                            'conjunto' => $conjunto,
                        ]);
                    
                    // 30% chance of having a tenant in the same apartment
                    if (rand(1, 100) <= 30) {
                        Resident::factory()
                            ->tenant()
                            ->active()
                            ->inApartment($apartmentNumber, $tower)
                            ->create([
                                'conjunto' => $conjunto,
                            ]);
                    }
                    
                    // 40% chance of having family members
                    if (rand(1, 100) <= 40) {
                        $familyMembers = rand(1, 3);
                        for ($j = 0; $j < $familyMembers; $j++) {
                            Resident::factory()
                                ->family()
                                ->active()
                                ->inApartment($apartmentNumber, $tower)
                                ->create([
                                    'conjunto' => $conjunto,
                                ]);
                        }
                    }
                }
            }
        }

        // Create some additional residents without towers (individual houses)
        Resident::factory()
            ->count(25)
            ->create([
                'conjunto' => 'Casas Independientes',
                'tower' => null,
                'apartment_number' => function () {
                    return 'Casa ' . rand(1, 50);
                }
            ]);

        // Create some inactive residents
        Resident::factory()
            ->count(15)
            ->inactive()
            ->create([
                'conjunto' => fake()->randomElement($conjuntos),
                'end_date' => fake()->dateTimeBetween('-1 year', 'now'),
                'notes' => 'Residente inactivo - contrato terminado'
            ]);

        // Create specific test residents for development
        $testResidents = [
            [
                'document_type' => 'CC',
                'document_number' => '12345678',
                'first_name' => 'Juan Carlos',
                'last_name' => 'Pérez González',
                'email' => 'juan.perez@test.com',
                'phone' => '601-2345',
                'mobile_phone' => '310-123-4567',
                'birth_date' => '1980-05-15',
                'gender' => 'M',
                'emergency_contact' => 'María González - 320-987-6543',
                'apartment_number' => '101',
                'tower' => 'A',
                'conjunto' => 'Conjunto Residencial Los Nogales',
                'resident_type' => 'Owner',
                'status' => 'Active',
                'start_date' => '2023-01-15',
                'notes' => 'Residente fundador del conjunto',
                'documents' => ['id_copy.pdf', 'contract.pdf', 'authorization.pdf']
            ],
            [
                'document_type' => 'CC',
                'document_number' => '87654321',
                'first_name' => 'María Alejandra',
                'last_name' => 'Rodríguez Silva',
                'email' => 'maria.rodriguez@test.com',
                'phone' => '601-5678',
                'mobile_phone' => '315-987-6543',
                'birth_date' => '1985-09-22',
                'gender' => 'F',
                'emergency_contact' => 'Carlos Silva - 301-555-0123',
                'apartment_number' => '205',
                'tower' => 'B',
                'conjunto' => 'Torres del Parque',
                'resident_type' => 'Tenant',
                'status' => 'Active',
                'start_date' => '2023-06-01',
                'end_date' => '2024-06-01',
                'notes' => 'Contrato de arrendamiento por 1 año',
                'documents' => ['id_copy.pdf', 'lease_contract.pdf']
            ],
            [
                'document_type' => 'CE',
                'document_number' => '98765432',
                'first_name' => 'Andrea',
                'last_name' => 'Martínez López',
                'email' => 'andrea.martinez@test.com',
                'phone' => null,
                'mobile_phone' => '318-456-7890',
                'birth_date' => '1992-03-10',
                'gender' => 'F',
                'emergency_contact' => 'Luis Martínez - 302-111-2222',
                'apartment_number' => '301',
                'tower' => 'C',
                'conjunto' => 'Ciudadela San José',
                'resident_type' => 'Family',
                'status' => 'Active',
                'start_date' => '2023-09-15',
                'notes' => 'Hija del propietario principal',
                'documents' => ['id_copy.pdf', 'family_authorization.pdf']
            ],
            [
                'document_type' => 'CC',
                'document_number' => '11223344',
                'first_name' => 'Carlos Enrique',
                'last_name' => 'Gómez Vargas',
                'email' => 'carlos.gomez@test.com',
                'phone' => '601-9999',
                'mobile_phone' => '300-555-1234',
                'birth_date' => '1975-12-05',
                'gender' => 'M',
                'emergency_contact' => 'Ana Vargas - 310-222-3333',
                'apartment_number' => 'Casa 15',
                'tower' => null,
                'conjunto' => 'Casas Independientes',
                'resident_type' => 'Owner',
                'status' => 'Inactive',
                'start_date' => '2022-03-01',
                'end_date' => '2023-12-31',
                'notes' => 'Vendió la propiedad en diciembre 2023',
                'documents' => ['id_copy.pdf', 'sale_contract.pdf']
            ]
        ];

        foreach ($testResidents as $resident) {
            Resident::create($resident);
        }

        $this->command->info('ResidentSeeder completed successfully!');
        $this->command->info('Created residents for ' . count($conjuntos) . ' conjuntos');
        $this->command->info('Total residents created: ' . Resident::count());
    }
}