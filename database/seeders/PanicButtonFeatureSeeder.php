<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Database\Seeder;

class PanicButtonFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Enabling panic button feature for all tenants...');

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $tenant->run(function () use ($tenant) {
                TenantFeature::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'feature' => 'panic_button',
                    ],
                    [
                        'enabled' => true,
                    ]
                );

                $this->command->info("Enabled panic button for tenant: {$tenant->id}");
            });
        }

        $this->command->info('Panic button feature enabled for all tenants successfully!');
    }
}
