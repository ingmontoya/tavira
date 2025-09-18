<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class SecurityUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a test security user for mobile app testing
        $securityUser = User::firstOrCreate(
            ['email' => 'porteria@test.com'],
            [
                'name' => 'Portería Test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign the porteria role using Spatie Permission
        $porteriaRole = Role::where('name', 'porteria')->first();
        if ($porteriaRole && ! $securityUser->hasRole('porteria')) {
            $securityUser->assignRole('porteria');
            $this->command->info('✅ Usuario de portería creado: porteria@test.com / password');
        } else {
            $this->command->info('ℹ️ Usuario de portería ya existe o rol no encontrado');
        }

        // Create another test user with resident role for comparison
        $residentUser = User::firstOrCreate(
            ['email' => 'residente@test.com'],
            [
                'name' => 'Residente Test',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $residentRole = Role::where('name', 'residente')->first();
        if ($residentRole && ! $residentUser->hasRole('residente')) {
            $residentUser->assignRole('residente');
            $this->command->info('✅ Usuario residente creado: residente@test.com / password');
        } else {
            $this->command->info('ℹ️ Usuario residente ya existe o rol no encontrado');
        }
    }
}
