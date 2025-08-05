<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\ApartmentType;
use App\Models\ConjuntoConfig;
use App\Models\Resident;
use Illuminate\Database\Seeder;

class TestConjuntoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🏢 Creating test conjunto configuration...');
        $conjunto = $this->createConjuntoConfig();

        $this->command->info('🏠 Creating apartment types...');
        $apartmentTypes = $this->createApartmentTypes($conjunto);

        $this->command->info('🏘️ Creating 20 test apartments...');
        $apartments = $this->createApartments($conjunto, $apartmentTypes);

        $this->command->info('👥 Creating test residents...');
        $this->createResidents($apartments);

        $this->command->info('✅ Test conjunto created successfully!');
        $this->command->info("🏢 Conjunto: {$conjunto->name}");
        $this->command->info('🏠 Total apartamentos: '.count($apartments));
    }

    private function createConjuntoConfig(): ConjuntoConfig
    {
        return ConjuntoConfig::create([
            'name' => 'Conjunto de Prueba Habitta',
            'description' => 'Conjunto residencial de prueba para validar facturación mensual',
            'number_of_towers' => 2,
            'floors_per_tower' => 2,
            'apartments_per_floor' => 5,
            'is_active' => true,
            'tower_names' => ['Torre 1', 'Torre 2'],
            'configuration_metadata' => [
                'floor_configuration' => [
                    1 => ['apartments_count' => 5, 'apartment_type' => 'Tipo A'],
                    2 => ['apartments_count' => 5, 'apartment_type' => 'Tipo B'],
                ],
            ],
        ]);
    }

    private function createApartmentTypes(ConjuntoConfig $conjunto): array
    {
        $types = [
            [
                'name' => 'Tipo A',
                'bedrooms' => 1,
                'bathrooms' => 1,
                'area_sqm' => 45.0,
                'administration_fee' => 180000.00,
                'description' => 'Apartamento de 1 habitación',
            ],
            [
                'name' => 'Tipo B',
                'bedrooms' => 2,
                'bathrooms' => 2,
                'area_sqm' => 65.0,
                'administration_fee' => 250000.00,
                'description' => 'Apartamento de 2 habitaciones',
            ],
        ];

        $apartmentTypes = [];
        foreach ($types as $type) {
            $apartmentTypes[] = ApartmentType::create([
                'conjunto_config_id' => $conjunto->id,
                'name' => $type['name'],
                'description' => $type['description'],
                'area_sqm' => $type['area_sqm'],
                'bedrooms' => $type['bedrooms'],
                'bathrooms' => $type['bathrooms'],
                'has_balcony' => true,
                'has_laundry_room' => true,
                'has_maid_room' => false,
                'coefficient' => round(($type['area_sqm'] / 1100) * 100, 6), // Simple coefficient
                'administration_fee' => $type['administration_fee'],
                'floor_positions' => [1, 2, 3, 4, 5],
                'features' => [],
            ]);
        }

        return $apartmentTypes;
    }

    private function createApartments(ConjuntoConfig $conjunto, array $apartmentTypes): array
    {
        $apartments = [];
        $apartmentNumber = 1;

        for ($tower = 1; $tower <= 2; $tower++) {
            for ($floor = 1; $floor <= 2; $floor++) {
                for ($position = 1; $position <= 5; $position++) {
                    // Assign apartment type based on floor
                    $typeIndex = $floor - 1; // Floor 1 = Tipo A (index 0), Floor 2 = Tipo B (index 1)
                    $apartmentType = $apartmentTypes[$typeIndex];

                    $apartments[] = Apartment::create([
                        'conjunto_config_id' => $conjunto->id,
                        'apartment_type_id' => $apartmentType->id,
                        'number' => str_pad($apartmentNumber, 3, '0', STR_PAD_LEFT),
                        'tower' => "Torre {$tower}",
                        'floor' => $floor,
                        'position_on_floor' => $position,
                        'status' => 'Occupied',
                        'monthly_fee' => $apartmentType->administration_fee,
                        'utilities' => [],
                        'features' => [],
                        'payment_status' => 'current',
                        'last_payment_date' => now()->subMonths(1),
                        'outstanding_balance' => 0.00,
                    ]);

                    $apartmentNumber++;
                }
            }
        }

        return $apartments;
    }

    private function createResidents(array $apartments): void
    {
        $firstNames = ['Carlos', 'María', 'José', 'Ana', 'Luis'];
        $lastNames = ['García', 'González', 'Rodríguez', 'Fernández', 'López'];

        foreach ($apartments as $index => $apartment) {
            $firstName = $firstNames[$index % 5];
            $lastName = $lastNames[$index % 5];

            Resident::create([
                'apartment_id' => $apartment->id,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'document_type' => 'CC',
                'document_number' => (string) (10000000 + $index),
                'phone' => '+57 3'.rand(10, 99).' '.rand(100, 999).' '.rand(1000, 9999),
                'email' => strtolower($firstName.'.'.$lastName.'.'.$apartment->number).'@test.com',
                'resident_type' => 'Owner',
                'status' => 'Active',
                'start_date' => now()->subYears(1),
                'documents' => [],
            ]);
        }
    }
}
