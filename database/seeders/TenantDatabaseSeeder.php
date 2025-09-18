<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class TenantDatabaseSeeder extends Seeder
{
    /**
     * Seed the tenant's database.
     */
    public function run(): void
    {
        $this->call([
            SecuritySettingsSeeder::class,
            RolePermissionSeeder::class,
            MaintenancePermissionsSeeder::class,
            // Note: ConjuntoConfigSeeder and ResidentSeeder are intentionally excluded
            // as these should be created by the tenant admin through the UI
        ]);
    }
}
