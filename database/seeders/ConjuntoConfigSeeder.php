<?php

namespace Database\Seeders;

use App\Models\ApartmentType;
use App\Models\ConjuntoConfig;
use Illuminate\Database\Seeder;

class ConjuntoConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Torres de Villa Campestre - Example from user
        $villaCampestre = ConjuntoConfig::create([
            'name' => 'Torres de Villa Campestre',
            'description' => 'Conjunto residencial con 5 torres, cada una con 15 pisos y 4 apartamentos por piso. Dos tipos de apartamentos: 86m² y 96m².',
            'number_of_towers' => 5,
            'floors_per_tower' => 15,
            'apartments_per_floor' => 4,
            'is_active' => true,
            'tower_names' => ['A', 'B', 'C', 'D', 'E'],
            'configuration_metadata' => [
                'architect' => 'Arquitectos Asociados S.A.',
                'construction_year' => '2020',
                'common_areas' => ['Piscina', 'Gimnasio', 'Salón Social', 'Parque Infantil', 'Cancha Múltiple'],
                'security_features' => ['Portería 24/7', 'Circuito Cerrado', 'Control de Acceso'],
            ],
        ]);

        // Apartment Type A - 86m²
        $typeA = ApartmentType::create([
            'conjunto_config_id' => $villaCampestre->id,
            'name' => 'Tipo A',
            'description' => 'Apartamento de 86m² con 3 habitaciones y 2 baños',
            'area_sqm' => 86.00,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'has_balcony' => true,
            'has_laundry_room' => true,
            'has_maid_room' => false,
            'coefficient' => 0.014000, // 1.4% del total
            'administration_fee' => 420000.00,
            'floor_positions' => [1, 3], // Positions 1 and 3 on each floor
            'features' => [
                'Cocina integral',
                'Pisos en porcelanato',
                'Ventanas con doble vidrio',
                'Closets empotrados',
            ],
        ]);

        // Apartment Type B - 96m²
        $typeB = ApartmentType::create([
            'conjunto_config_id' => $villaCampestre->id,
            'name' => 'Tipo B',
            'description' => 'Apartamento de 96m² con 3 habitaciones, 2 baños y cuarto de servicio',
            'area_sqm' => 96.00,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'has_balcony' => true,
            'has_laundry_room' => true,
            'has_maid_room' => true,
            'coefficient' => 0.016000, // 1.6% del total
            'administration_fee' => 480000.00,
            'floor_positions' => [2, 4], // Positions 2 and 4 on each floor
            'features' => [
                'Cocina integral con isla',
                'Pisos en porcelanato',
                'Ventanas con doble vidrio',
                'Closets empotrados',
                'Cuarto de servicio con baño',
                'Balcón amplio',
            ],
        ]);

        // Generate apartments for Villa Campestre
        $villaCampestre->generateApartments();

        // Additional ejemplo: Conjunto Los Sauces (smaller example)
        $losSauces = ConjuntoConfig::create([
            'name' => 'Conjunto Los Sauces',
            'description' => 'Conjunto residencial familiar con 3 torres, 8 pisos y 2 apartamentos por piso.',
            'number_of_towers' => 3,
            'floors_per_tower' => 8,
            'apartments_per_floor' => 2,
            'is_active' => true,
            'tower_names' => ['Norte', 'Sur', 'Central'],
            'configuration_metadata' => [
                'architect' => 'Diseños Modernos Ltda.',
                'construction_year' => '2018',
                'common_areas' => ['Piscina', 'Salón Social', 'Parque Infantil'],
                'security_features' => ['Portería', 'Circuito Cerrado'],
            ],
        ]);

        // Apartment Type Único - 72m²
        $typeUnico = ApartmentType::create([
            'conjunto_config_id' => $losSauces->id,
            'name' => 'Estándar',
            'description' => 'Apartamento estándar de 72m² con 2 habitaciones y 2 baños',
            'area_sqm' => 72.00,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'has_balcony' => true,
            'has_laundry_room' => true,
            'has_maid_room' => false,
            'coefficient' => 0.020833, // 2.0833% del total (1/48)
            'administration_fee' => 280000.00,
            'floor_positions' => [1, 2], // Both positions on each floor
            'features' => [
                'Cocina integral',
                'Pisos en cerámica',
                'Closets empotrados',
                'Balcón con vista',
            ],
        ]);

        // Generate apartments for Los Sauces
        $losSauces->generateApartments();

        // Casas Campestres San Miguel (houses instead of apartments)
        $sanMiguel = ConjuntoConfig::create([
            'name' => 'Casas Campestres San Miguel',
            'description' => 'Conjunto de casas campestres con 50 unidades distribuidas en 5 sectores.',
            'number_of_towers' => 5, // We'll use "towers" as "sectors"
            'floors_per_tower' => 1, // Only ground floor
            'apartments_per_floor' => 10, // 10 houses per sector
            'is_active' => true,
            'tower_names' => ['Sector 1', 'Sector 2', 'Sector 3', 'Sector 4', 'Sector 5'],
            'configuration_metadata' => [
                'architect' => 'Arquitectura Rural S.A.',
                'construction_year' => '2019',
                'common_areas' => ['Casa Club', 'Piscina', 'Cancha de Tenis', 'Senderos Ecológicos'],
                'security_features' => ['Portería Principal', 'Rondas de Seguridad'],
            ],
        ]);

        // House Type A - 120m²
        $houseTypeA = ApartmentType::create([
            'conjunto_config_id' => $sanMiguel->id,
            'name' => 'Casa Tipo A',
            'description' => 'Casa de 120m² con 3 habitaciones, 2 baños y jardín privado',
            'area_sqm' => 120.00,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'has_balcony' => false, // Houses don't have balconies
            'has_laundry_room' => true,
            'has_maid_room' => true,
            'coefficient' => 0.020000, // 2.0% del total
            'administration_fee' => 320000.00,
            'floor_positions' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10], // All positions
            'features' => [
                'Jardín privado',
                'Garaje para 2 carros',
                'Terraza cubierta',
                'Cocina integral',
                'Pisos en porcelanato',
                'Cuarto de servicio',
            ],
        ]);

        // Generate houses for San Miguel
        $sanMiguel->generateApartments();

        $this->command->info('ConjuntoConfigSeeder completed successfully!');
        $this->command->info('Created configurations for:');
        $this->command->info('- Torres de Villa Campestre: '.$villaCampestre->total_apartments.' apartments');
        $this->command->info('- Conjunto Los Sauces: '.$losSauces->total_apartments.' apartments');
        $this->command->info('- Casas Campestres San Miguel: '.$sanMiguel->total_apartments.' houses');
        $this->command->info('Total apartments/houses generated: '.
            ($villaCampestre->total_apartments + $losSauces->total_apartments + $sanMiguel->total_apartments));
    }
}
