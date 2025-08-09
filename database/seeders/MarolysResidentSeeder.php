<?php

namespace Database\Seeders;

use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class MarolysResidentSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'marolys@gmail.com')->first();

        if ($user) {
            // Assign resident role
            $residentRole = Role::where('name', 'residente')->first();
            if ($residentRole && ! $user->hasRole('residente')) {
                $user->assignRole($residentRole);
                $this->command->info("Rol 'residente' asignado al usuario: {$user->email}");
            }

            // Check if resident record exists
            $resident = Resident::where('email', 'marolys@gmail.com')->first();

            if ($resident) {
                $this->command->info("Residente ya existe: {$resident->full_name}");
                $this->command->info("Apartamento: {$resident->apartment->full_address}");
            } else {
                $this->command->warn('No se encontró registro de residente para: marolys@gmail.com');
            }

            // Check permissions
            $hasPermission = $user->can('view_account_statement');
            $this->command->info('Puede ver estado de cuenta: '.($hasPermission ? 'SÍ' : 'NO'));

            $this->command->info('Usuario configurado correctamente. Puede acceder al estado de cuenta en: /account-statement');

        } else {
            $this->command->error('No se encontró usuario con email: marolys@gmail.com');
        }
    }
}
