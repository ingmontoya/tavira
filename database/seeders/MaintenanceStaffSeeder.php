<?php

namespace Database\Seeders;

use App\Models\ConjuntoConfig;
use App\Models\MaintenanceStaff;
use Illuminate\Database\Seeder;

class MaintenanceStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $conjuntoConfig = ConjuntoConfig::first();

        if (! $conjuntoConfig) {
            $this->command->warn('No se encontró configuración de conjunto. Asegúrate de ejecutar primero ConjuntoConfigSeeder.');

            return;
        }

        $staff = [
            [
                'name' => 'Carlos Martínez',
                'phone' => '+ +44 7447 313219',
                'email' => 'carlos.martinez@email.com',
                'specialties' => ['Plomería', 'Electricidad'],
                'hourly_rate' => 25000.00,
                'is_internal' => true,
                'availability_schedule' => [
                    'monday' => ['start' => '08:00', 'end' => '17:00'],
                    'tuesday' => ['start' => '08:00', 'end' => '17:00'],
                    'wednesday' => ['start' => '08:00', 'end' => '17:00'],
                    'thursday' => ['start' => '08:00', 'end' => '17:00'],
                    'friday' => ['start' => '08:00', 'end' => '17:00'],
                    'saturday' => ['start' => '08:00', 'end' => '12:00'],
                ],
            ],
            [
                'name' => 'María González',
                'phone' => '+57 301 234 5678',
                'email' => 'maria.gonzalez@email.com',
                'specialties' => ['Jardinería', 'Limpieza'],
                'hourly_rate' => 20000.00,
                'is_internal' => true,
                'availability_schedule' => [
                    'monday' => ['start' => '06:00', 'end' => '14:00'],
                    'tuesday' => ['start' => '06:00', 'end' => '14:00'],
                    'wednesday' => ['start' => '06:00', 'end' => '14:00'],
                    'thursday' => ['start' => '06:00', 'end' => '14:00'],
                    'friday' => ['start' => '06:00', 'end' => '14:00'],
                    'saturday' => ['start' => '06:00', 'end' => '12:00'],
                ],
            ],
            [
                'name' => 'Juan Pablo Rojas',
                'phone' => '+57 302 345 6789',
                'email' => 'jp.rojas@email.com',
                'specialties' => ['Pintura', 'Mejoras'],
                'hourly_rate' => 35000.00,
                'is_internal' => false,
                'availability_schedule' => [
                    'monday' => ['start' => '09:00', 'end' => '18:00'],
                    'tuesday' => ['start' => '09:00', 'end' => '18:00'],
                    'wednesday' => ['start' => '09:00', 'end' => '18:00'],
                    'thursday' => ['start' => '09:00', 'end' => '18:00'],
                    'friday' => ['start' => '09:00', 'end' => '18:00'],
                ],
            ],
            [
                'name' => 'Andrés Jiménez',
                'phone' => '+57 303 456 7890',
                'email' => 'andres.jimenez@email.com',
                'specialties' => ['Seguridad', 'Emergencias'],
                'hourly_rate' => 30000.00,
                'is_internal' => true,
                'availability_schedule' => [
                    'monday' => ['start' => '00:00', 'end' => '23:59'],
                    'tuesday' => ['start' => '00:00', 'end' => '23:59'],
                    'wednesday' => ['start' => '00:00', 'end' => '23:59'],
                    'thursday' => ['start' => '00:00', 'end' => '23:59'],
                    'friday' => ['start' => '00:00', 'end' => '23:59'],
                    'saturday' => ['start' => '00:00', 'end' => '23:59'],
                    'sunday' => ['start' => '00:00', 'end' => '23:59'],
                ],
            ],
            [
                'name' => 'Técnicos Ascensores S.A.S.',
                'phone' => '+57 304 567 8901',
                'email' => 'servicio@tecnicosascensores.com',
                'specialties' => ['Ascensores'],
                'hourly_rate' => 80000.00,
                'is_internal' => false,
                'availability_schedule' => [
                    'monday' => ['start' => '08:00', 'end' => '17:00'],
                    'tuesday' => ['start' => '08:00', 'end' => '17:00'],
                    'wednesday' => ['start' => '08:00', 'end' => '17:00'],
                    'thursday' => ['start' => '08:00', 'end' => '17:00'],
                    'friday' => ['start' => '08:00', 'end' => '17:00'],
                ],
            ],
            [
                'name' => 'AquaTech Piscinas',
                'phone' => '+57 305 678 9012',
                'email' => 'info@aquatech.com',
                'specialties' => ['Piscina'],
                'hourly_rate' => 50000.00,
                'is_internal' => false,
                'availability_schedule' => [
                    'tuesday' => ['start' => '10:00', 'end' => '16:00'],
                    'thursday' => ['start' => '10:00', 'end' => '16:00'],
                    'saturday' => ['start' => '08:00', 'end' => '12:00'],
                ],
            ],
        ];

        foreach ($staff as $person) {
            MaintenanceStaff::create([
                'conjunto_config_id' => $conjuntoConfig->id,
                ...$person,
            ]);
        }

        $this->command->info('Personal de mantenimiento creado exitosamente.');
    }
}
