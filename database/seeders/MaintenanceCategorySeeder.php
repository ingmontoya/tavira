<?php

namespace Database\Seeders;

use App\Models\ConjuntoConfig;
use App\Models\MaintenanceCategory;
use Illuminate\Database\Seeder;

class MaintenanceCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conjuntoConfig = ConjuntoConfig::first();

        if (! $conjuntoConfig) {
            if ($this->command) {
                $this->command->warn('No se encontró configuración de conjunto. Asegúrate de ejecutar primero ConjuntoConfigSeeder.');
            }

            return;
        }

        $categories = [
            [
                'name' => 'Plomería',
                'description' => 'Reparaciones y mantenimiento de sistemas de plomería',
                'color' => '#3B82F6',
                'priority_level' => 2,
                'requires_approval' => false,
                'estimated_hours' => 2.0,
            ],
            [
                'name' => 'Electricidad',
                'description' => 'Instalaciones y reparaciones eléctricas',
                'color' => '#F59E0B',
                'priority_level' => 1,
                'requires_approval' => false,
                'estimated_hours' => 3.0,
            ],
            [
                'name' => 'Jardinería',
                'description' => 'Mantenimiento de zonas verdes y jardines',
                'color' => '#10B981',
                'priority_level' => 4,
                'requires_approval' => false,
                'estimated_hours' => 4.0,
            ],
            [
                'name' => 'Limpieza',
                'description' => 'Limpieza general de áreas comunes',
                'color' => '#8B5CF6',
                'priority_level' => 3,
                'requires_approval' => false,
                'estimated_hours' => 1.5,
            ],
            [
                'name' => 'Pintura',
                'description' => 'Trabajos de pintura y retoque',
                'color' => '#EF4444',
                'priority_level' => 3,
                'requires_approval' => true,
                'estimated_hours' => 6.0,
            ],
            [
                'name' => 'Seguridad',
                'description' => 'Sistemas de seguridad y vigilancia',
                'color' => '#DC2626',
                'priority_level' => 1,
                'requires_approval' => true,
                'estimated_hours' => 2.0,
            ],
            [
                'name' => 'Ascensores',
                'description' => 'Mantenimiento y reparación de ascensores',
                'color' => '#6B7280',
                'priority_level' => 1,
                'requires_approval' => false,
                'estimated_hours' => 4.0,
            ],
            [
                'name' => 'Piscina',
                'description' => 'Mantenimiento de piscina y sistemas de filtración',
                'color' => '#06B6D4',
                'priority_level' => 2,
                'requires_approval' => false,
                'estimated_hours' => 3.0,
            ],
            [
                'name' => 'Emergencias',
                'description' => 'Reparaciones urgentes y emergencias',
                'color' => '#DC2626',
                'priority_level' => 1,
                'requires_approval' => false,
                'estimated_hours' => 1.0,
            ],
            [
                'name' => 'Mejoras',
                'description' => 'Proyectos de mejora y modernización',
                'color' => '#7C3AED',
                'priority_level' => 4,
                'requires_approval' => true,
                'estimated_hours' => 8.0,
            ],
        ];

        foreach ($categories as $category) {
            MaintenanceCategory::create([
                'conjunto_config_id' => $conjuntoConfig->id,
                ...$category,
            ]);
        }

        // Only call info if command is available (when run from Artisan)
        if ($this->command) {
            $this->command->info('Categorías de mantenimiento creadas exitosamente.');
        }
    }
}
