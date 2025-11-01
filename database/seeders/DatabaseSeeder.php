<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Only create test users in local/development environments
        if (app()->environment('local', 'development')) {
            // User::factory(10)->create();

            User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $this->call([
            SecuritySettingsSeeder::class,
            RolePermissionSeeder::class,
            MaintenancePermissionsSeeder::class,
            ConjuntoConfigSeeder::class,
            ResidentSeeder::class,
        ]);
    }
}
