<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Seeder;

class AnnouncementsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user (admin) as creator
        $admin = User::first();

        if (! $admin) {
            $this->command->warn('No user found. Please create a user first.');

            return;
        }

        $announcements = [
            [
                'title' => 'Mantenimiento Preventivo de Ascensores',
                'content' => 'Estimados residentes,

Informamos que el próximo lunes 15 de enero se realizará el mantenimiento preventivo de todos los ascensores del edificio. El trabajo se ejecutará entre las 8:00 AM y 5:00 PM.

Durante este tiempo, los ascensores estarán fuera de servicio por períodos intermitentes. Recomendamos usar las escaleras cuando sea posible.

Gracias por su comprensión.

Administración',
                'priority' => 'important',
                'type' => 'maintenance',
                'status' => 'published',
                'is_pinned' => true,
                'requires_confirmation' => false,
                'published_at' => now()->subDays(2),
                'expires_at' => now()->addDays(5),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Corte de Agua Programado',
                'content' => 'Estimados propietarios y residentes,

La empresa de acueducto ha programado un corte de agua para el día miércoles 17 de enero desde las 6:00 AM hasta las 2:00 PM aproximadamente.

Este corte es necesario para realizar reparaciones en la red principal de suministro.

Recomendaciones:
- Almacenen agua suficiente para sus necesidades básicas
- El tanque de reserva del edificio tendrá agua limitada para emergencias
- Eviten usar lavadoras y lavavajillas durante este período

Agradecemos su comprensión y colaboración.

Administración del Conjunto',
                'priority' => 'urgent',
                'type' => 'administrative',
                'status' => 'published',
                'is_pinned' => false,
                'requires_confirmation' => true,
                'published_at' => now()->subDay(),
                'expires_at' => now()->addDays(3),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Asamblea General Ordinaria 2024',
                'content' => 'Cordial saludo,

Convocamos a todos los propietarios a la Asamblea General Ordinaria que se realizará el día sábado 20 de enero de 2024 a las 9:00 AM en el salón comunal del primer piso.

ORDEN DEL DÍA:
1. Verificación de quórum
2. Lectura y aprobación del acta anterior
3. Informe de administración 2023
4. Estados financieros y presupuesto 2024
5. Elección de miembros del Consejo de Administración
6. Proposiciones y varios

Es importante su participación para tomar decisiones importantes sobre nuestro conjunto residencial.

Administración',
                'priority' => 'important',
                'type' => 'administrative',
                'status' => 'published',
                'is_pinned' => true,
                'requires_confirmation' => true,
                'published_at' => now()->subDays(5),
                'expires_at' => now()->addDays(10),
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Nuevas Normas de Convivencia',
                'content' => 'Estimados residentes,

El Consejo de Administración ha aprobado las siguientes normas de convivencia que entrarán en vigencia a partir del 1 de febrero:

HORARIOS DE RUIDO:
- Lunes a viernes: silencio después de las 10:00 PM
- Fines de semana: silencio después de las 11:00 PM
- Domingos y festivos: silencio después de las 9:00 PM

USO DE ZONAS COMUNES:
- Salón comunal: reservar con 48 horas de anticipación
- Gimnasio: máximo 45 minutos por sesión
- Piscina: horario de 6:00 AM a 10:00 PM

MASCOTAS:
- Siempre con correa en zonas comunes
- Los propietarios son responsables de recoger los desechos
- Registro obligatorio en administración

El incumplimiento de estas normas puede generar llamados de atención y multas según el reglamento.

Consejo de Administración',
                'priority' => 'normal',
                'type' => 'administrative',
                'status' => 'published',
                'is_pinned' => false,
                'requires_confirmation' => true,
                'published_at' => now()->subDays(7),
                'expires_at' => null,
                'created_by' => $admin->id,
            ],
            [
                'title' => 'Fumigación Mensual',
                'content' => 'Estimados residentes,

Informamos que este viernes 19 de enero se realizará la fumigación mensual de todas las áreas comunes del edificio.

HORARIO: 7:00 AM - 11:00 AM

ÁREAS A FUMIGAR:
- Sótanos y parqueaderos
- Escaleras de emergencia
- Cuartos de basuras
- Zonas verdes
- Salón comunal y gimnasio

RECOMENDACIONES:
- Mantengan cerradas las ventanas que den a zonas comunes
- No transiten por las áreas durante la fumigación
- Si tienen mascotas, manténganlas en sus apartamentos
- El producto utilizado es seguro pero puede causar irritación

La empresa certificada iniciará por el sótano y subirá gradualmente.

Administración',
                'priority' => 'normal',
                'type' => 'maintenance',
                'status' => 'draft',
                'is_pinned' => false,
                'requires_confirmation' => false,
                'published_at' => null,
                'expires_at' => now()->addDays(2),
                'created_by' => $admin->id,
            ],
        ];

        foreach ($announcements as $announcementData) {
            Announcement::create($announcementData);
        }

        $this->command->info('Announcements seeded successfully!');
    }
}
