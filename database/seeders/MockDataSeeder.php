<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\ConjuntoConfig;
use App\Models\ApartmentType;
use App\Models\Apartment;
use App\Models\Resident;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class MockDataSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('es_ES');
        
        // Crear usuario administrador de ejemplo si no existe
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@habitta.com'],
            [
                'name' => 'Administrador Principal',
                'password' => Hash::make('password'),
                'role' => 'company',
                'conjunto_config_id' => null,
            ]
        );

        // Crear conjuntos de ejemplo
        $conjuntos = [
            [
                'name' => 'Conjunto Residencial Vista Hermosa',
                'description' => 'Moderno conjunto residencial ubicado en el norte de Bogotá con excelentes acabados y zonas verdes.',
                'number_of_towers' => 3,
                'floors_per_tower' => 8,
                'apartments_per_floor' => 4,
                'is_active' => true,
                'tower_names' => json_encode(['A', 'B', 'C']),
                'configuration_metadata' => json_encode([
                    'address' => 'Carrera 15 #85-23, Bogotá',
                    'phone' => '601-234-5678',
                    'email' => 'administracion@vistahermosa.com'
                ]),
            ],
            [
                'name' => 'Torres del Parque Real',
                'description' => 'Exclusivo complejo de torres con vista panorámica y amplias zonas de recreación.',
                'number_of_towers' => 2,
                'floors_per_tower' => 12,
                'apartments_per_floor' => 6,
                'is_active' => true,
                'tower_names' => json_encode(['Norte', 'Sur']),
                'configuration_metadata' => json_encode([
                    'address' => 'Calle 127 #45-67, Bogotá',
                    'phone' => '601-345-6789',
                    'email' => 'info@parquereal.com'
                ]),
            ],
            [
                'name' => 'Conjunto Los Eucaliptos',
                'description' => 'Acogedor conjunto familiar con amplias zonas verdes y juegos infantiles.',
                'number_of_towers' => 4,
                'floors_per_tower' => 6,
                'apartments_per_floor' => 3,
                'is_active' => true,
                'tower_names' => json_encode(['1', '2', '3', '4']),
                'configuration_metadata' => json_encode([
                    'address' => 'Avenida 68 #134-89, Bogotá',
                    'phone' => '601-456-7890',
                    'email' => 'admin@eucaliptos.com'
                ]),
            ],
        ];

        foreach ($conjuntos as $conjuntoData) {
            $conjunto = ConjuntoConfig::firstOrCreate(
                ['name' => $conjuntoData['name']],
                $conjuntoData
            );

            // Crear tipos de apartamento para cada conjunto
            $apartmentTypes = [
                [
                    'name' => 'Tipo A',
                    'description' => 'Apartamento compacto ideal para parejas o profesionales',
                    'area_sqm' => $faker->randomFloat(2, 45, 65),
                    'bedrooms' => 1,
                    'bathrooms' => 1,
                    'has_balcony' => $faker->boolean(60),
                    'has_laundry_room' => false,
                    'has_maid_room' => false,
                    'coefficient' => $faker->randomFloat(6, 0.015, 0.025),
                    'administration_fee' => $faker->numberBetween(180000, 220000),
                    'floor_positions' => json_encode([1]),
                    'features' => json_encode(['Cocina integral', 'Closet empotrado']),
                    'conjunto_config_id' => $conjunto->id,
                ],
                [
                    'name' => 'Tipo B',
                    'description' => 'Apartamento de 2 habitaciones perfecto para familias pequeñas',
                    'area_sqm' => $faker->randomFloat(2, 65, 85),
                    'bedrooms' => 2,
                    'bathrooms' => 2,
                    'has_balcony' => $faker->boolean(80),
                    'has_laundry_room' => true,
                    'has_maid_room' => false,
                    'coefficient' => $faker->randomFloat(6, 0.025, 0.035),
                    'administration_fee' => $faker->numberBetween(250000, 320000),
                    'floor_positions' => json_encode([2, 3]),
                    'features' => json_encode(['Cocina integral', 'Zona de lavandería', 'Balcón']),
                    'conjunto_config_id' => $conjunto->id,
                ],
                [
                    'name' => 'Tipo C',
                    'description' => 'Apartamento amplio de 3 habitaciones para familias grandes',
                    'area_sqm' => $faker->randomFloat(2, 85, 110),
                    'bedrooms' => 3,
                    'bathrooms' => 2,
                    'has_balcony' => true,
                    'has_laundry_room' => true,
                    'has_maid_room' => $faker->boolean(50),
                    'coefficient' => $faker->randomFloat(6, 0.035, 0.045),
                    'administration_fee' => $faker->numberBetween(350000, 450000),
                    'floor_positions' => json_encode([4]),
                    'features' => json_encode(['Cocina integral', 'Zona de lavandería', 'Balcón', 'Walk-in closet']),
                    'conjunto_config_id' => $conjunto->id,
                ],
                [
                    'name' => 'Penthouse',
                    'description' => 'Apartamento de lujo en los últimos pisos con vista panorámica',
                    'area_sqm' => $faker->randomFloat(2, 120, 180),
                    'bedrooms' => 3,
                    'bathrooms' => 3,
                    'has_balcony' => true,
                    'has_laundry_room' => true,
                    'has_maid_room' => true,
                    'coefficient' => $faker->randomFloat(6, 0.045, 0.065),
                    'administration_fee' => $faker->numberBetween(600000, 900000),
                    'floor_positions' => json_encode([1, 2]),
                    'features' => json_encode(['Cocina integral premium', 'Terraza privada', 'Jacuzzi', 'Estudio', 'Doble balcón']),
                    'conjunto_config_id' => $conjunto->id,
                ],
            ];

            foreach ($apartmentTypes as $typeData) {
                ApartmentType::firstOrCreate(
                    [
                        'name' => $typeData['name'],
                        'conjunto_config_id' => $typeData['conjunto_config_id']
                    ],
                    $typeData
                );
            }

            // Crear apartamentos para cada conjunto
            $this->createApartments($conjunto, $faker);
        }

        $this->command->info('Mock data seeded successfully!');
    }

    private function createApartments(ConjuntoConfig $conjunto, $faker)
    {
        $apartmentTypes = ApartmentType::where('conjunto_config_id', $conjunto->id)->get();
        $apartmentCount = 0;
        $totalApartments = $conjunto->number_of_towers * $conjunto->floors_per_tower * $conjunto->apartments_per_floor;

        for ($tower = 1; $tower <= $conjunto->number_of_towers; $tower++) {
            for ($floor = 1; $floor <= $conjunto->floors_per_tower; $floor++) {
                for ($apt = 1; $apt <= $conjunto->apartments_per_floor; $apt++) {
                    $apartmentNumber = sprintf('%d%02d%02d', $tower, $floor, $apt);
                    $apartmentType = $apartmentTypes->random();
                    
                    // Determinar estado del apartamento (80% ocupado, 15% disponible, 5% mantenimiento)
                    $statusRand = $faker->numberBetween(1, 100);
                    if ($statusRand <= 80) {
                        $status = 'Occupied';
                    } elseif ($statusRand <= 95) {
                        $status = 'Available';
                    } else {
                        $status = 'Maintenance';
                    }

                    $apartment = Apartment::firstOrCreate(
                        [
                            'number' => $apartmentNumber,
                            'conjunto_config_id' => $conjunto->id,
                        ],
                        [
                            'tower' => (string)$tower,
                            'floor' => $floor,
                            'position_on_floor' => $apt,
                            'apartment_type_id' => $apartmentType->id,
                            'status' => $status,
                            'monthly_fee' => $apartmentType->administration_fee,
                            'utilities' => json_encode([
                                'water_meter' => $faker->numberBetween(1000, 9999),
                                'electricity_meter' => $faker->numberBetween(10000, 99999),
                                'gas_meter' => $faker->boolean(70) ? $faker->numberBetween(100, 999) : null,
                            ]),
                            'features' => json_encode([
                                'view' => $faker->randomElement(['Interior', 'Exterior', 'Panorámica']),
                                'orientation' => $faker->randomElement(['Norte', 'Sur', 'Este', 'Oeste']),
                                'parking_spots' => $faker->numberBetween(1, 2),
                            ]),
                            'notes' => $faker->optional(0.2)->sentence,
                            'conjunto_config_id' => $conjunto->id,
                        ]
                    );

                    // Crear residentes para apartamentos ocupados
                    if ($status === 'Occupied') {
                        $this->createResidents($apartment, $faker);
                    }

                    $apartmentCount++;
                }
            }
        }

        $this->command->info("Created {$apartmentCount} apartments for {$conjunto->name}");
    }

    private function createResidents(Apartment $apartment, $faker)
    {
        // Crear 1-3 residentes por apartamento ocupado
        $residentCount = $faker->numberBetween(1, 3);
        $residentTypes = ['Owner', 'Tenant', 'Family'];
        
        for ($i = 0; $i < $residentCount; $i++) {
            $firstName = $faker->firstName;
            $lastName = $faker->lastName . ' ' . $faker->lastName;
            $documentNumber = $faker->unique()->numerify('##########');
            
            // Primer residente es propietario o arrendatario, el resto familia
            $residentType = $i === 0 ? $faker->randomElement(['Owner', 'Tenant']) : 'Family';
            
            Resident::firstOrCreate(
                [
                    'document_number' => $documentNumber,
                ],
                [
                    'document_type' => $faker->randomElement(['CC', 'CE', 'PP']),
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => strtolower($firstName . '.' . str_replace(' ', '', $lastName) . '@email.com'),
                    'phone' => $faker->regexify('60[1-9][0-9]{7}'),
                    'mobile_phone' => $faker->regexify('3[0-9]{9}'),
                    'birth_date' => $faker->dateTimeBetween('-70 years', '-18 years')->format('Y-m-d'),
                    'gender' => $faker->randomElement(['M', 'F']),
                    'emergency_contact' => $faker->name . ' - ' . $faker->regexify('3[0-9]{9}'),
                    'apartment_id' => $apartment->id,
                    'resident_type' => $residentType,
                    'status' => $faker->randomElement(['Active', 'Active', 'Active', 'Inactive']), // 75% activos
                    'start_date' => $faker->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
                    'end_date' => $faker->optional(0.1)->dateTimeBetween('now', '+1 year')?->format('Y-m-d'),
                    'notes' => $faker->optional(0.3)->sentence,
                    'email_notifications' => $faker->boolean(85), // 85% acepta notificaciones
                    'whatsapp_notifications' => $faker->boolean(70), // 70% acepta WhatsApp
                    'whatsapp_number' => $faker->boolean(70) ? $faker->regexify('3[0-9]{9}') : null,
                ]
            );
        }
    }
}