<?php

namespace Database\Seeders;

use App\Models\Apartment;
use App\Models\Resident;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AccountStatementTestSeeder extends Seeder
{
    public function run(): void
    {
        // Create a test user that will link to a resident
        $testUser = User::firstOrCreate(
            ['email' => 'resident@test.com'],
            [
                'name' => 'Juan Pérez',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assign resident role
        $residentRole = Role::where('name', 'residente')->first();
        if ($residentRole) {
            $testUser->assignRole($residentRole);
        }

        // Note: conjunto_config_id field may not exist in current database schema
        // This is okay as the relationship is established through the resident->apartment connection

        // Get the first apartment
        $apartment = Apartment::first();

        if ($apartment) {
            // Create or update resident with the same email as the user
            Resident::updateOrCreate(
                ['email' => 'resident@test.com'],
                [
                    'document_type' => 'CC',
                    'document_number' => '12345678',
                    'first_name' => 'Juan',
                    'last_name' => 'Pérez',
                    'phone' => '3001234567',
                    'apartment_id' => $apartment->id,
                    'resident_type' => 'Owner',
                    'status' => 'Active',
                    'start_date' => now()->subYear(),
                ]
            );

            $this->command->info('Created test user: resident@test.com (password: password)');
            $this->command->info("Linked to apartment: {$apartment->full_address}");
            $this->command->info('You can now test the account statement functionality!');
        } else {
            $this->command->warn('No apartments found. Please create apartments first.');
        }
    }
}
