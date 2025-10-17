<?php

namespace Database\Seeders;

use App\Models\ProviderCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ProviderCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Mantenimiento Eléctrico',
                'description' => 'Instalación, reparación y mantenimiento de sistemas eléctricos',
                'sort_order' => 10,
            ],
            [
                'name' => 'Plomería e Hidráulica',
                'description' => 'Servicios de plomería, reparación de tuberías y sistemas hidráulicos',
                'sort_order' => 20,
            ],
            [
                'name' => 'Pintura y Acabados',
                'description' => 'Servicios de pintura interior y exterior, acabados decorativos',
                'sort_order' => 30,
            ],
            [
                'name' => 'Jardinería y Paisajismo',
                'description' => 'Mantenimiento de jardines, poda, diseño de paisajes',
                'sort_order' => 40,
            ],
            [
                'name' => 'Aseo y Limpieza',
                'description' => 'Servicios de aseo, limpieza de áreas comunes y especializadas',
                'sort_order' => 50,
            ],
            [
                'name' => 'Seguridad',
                'description' => 'Servicios de vigilancia, sistemas de seguridad, CCTV',
                'sort_order' => 60,
            ],
            [
                'name' => 'Carpintería',
                'description' => 'Trabajos en madera, muebles, reparaciones de carpintería',
                'sort_order' => 70,
            ],
            [
                'name' => 'Cerrajería',
                'description' => 'Instalación y reparación de cerraduras, duplicado de llaves',
                'sort_order' => 80,
            ],
            [
                'name' => 'Sistemas de Gas',
                'description' => 'Instalación y mantenimiento de sistemas de gas natural y GLP',
                'sort_order' => 90,
            ],
            [
                'name' => 'Aire Acondicionado y Refrigeración',
                'description' => 'Instalación, mantenimiento y reparación de sistemas de climatización',
                'sort_order' => 100,
            ],
            [
                'name' => 'Impermeabilización',
                'description' => 'Servicios de impermeabilización de techos, terrazas y fachadas',
                'sort_order' => 110,
            ],
            [
                'name' => 'Ascensores',
                'description' => 'Mantenimiento y reparación de ascensores y montacargas',
                'sort_order' => 120,
            ],
            [
                'name' => 'Piscinas',
                'description' => 'Mantenimiento, limpieza y reparación de piscinas',
                'sort_order' => 130,
            ],
            [
                'name' => 'Zonas Verdes y Parques',
                'description' => 'Mantenimiento de zonas verdes, parques infantiles y canchas',
                'sort_order' => 140,
            ],
            [
                'name' => 'Control de Plagas',
                'description' => 'Fumigación, control de roedores e insectos',
                'sort_order' => 150,
            ],
            [
                'name' => 'Suministros y Materiales',
                'description' => 'Proveedores de materiales de construcción y suministros',
                'sort_order' => 160,
            ],
            [
                'name' => 'Servicios Legales',
                'description' => 'Asesoría jurídica, cobranzas, gestión legal',
                'sort_order' => 170,
            ],
            [
                'name' => 'Servicios Contables',
                'description' => 'Contabilidad, revisoría fiscal, declaraciones tributarias',
                'sort_order' => 180,
            ],
            [
                'name' => 'Servicios de Tecnología',
                'description' => 'Soporte IT, redes, sistemas de internet y telecomunicaciones',
                'sort_order' => 190,
            ],
            [
                'name' => 'Obras Civiles',
                'description' => 'Construcción, remodelaciones, obra civil en general',
                'sort_order' => 200,
            ],
            [
                'name' => 'Otros Servicios',
                'description' => 'Otros servicios no clasificados en las categorías anteriores',
                'sort_order' => 1000,
            ],
        ];

        foreach ($categories as $category) {
            ProviderCategory::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'description' => $category['description'],
                'is_active' => true,
                'sort_order' => $category['sort_order'],
            ]);
        }
    }
}
