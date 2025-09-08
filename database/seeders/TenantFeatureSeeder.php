<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\TenantFeature;
use Illuminate\Database\Seeder;

class TenantFeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenants = Tenant::all();
        
        if ($tenants->isEmpty()) {
            $this->command->warn('No tenants found. Please create tenants first.');
            return;
        }

        $basicFeatures = ['correspondence', 'announcements', 'support_tickets'];
        $standardFeatures = ['correspondence', 'maintenance_requests', 'announcements', 'support_tickets', 'documents'];
        $premiumFeatures = ['correspondence', 'maintenance_requests', 'visitor_management', 'accounting', 'reservations', 'announcements', 'documents', 'support_tickets', 'payment_agreements', 'voting'];

        $plans = [
            'basic' => $basicFeatures,
            'standard' => $standardFeatures,
            'premium' => $premiumFeatures,
        ];

        foreach ($tenants as $tenant) {
            // Randomly assign a plan to each tenant for demo purposes
            $planType = array_rand($plans);
            $features = $plans[$planType];
            
            $this->command->info("Assigning {$planType} plan to tenant {$tenant->id}");
            
            // Enable features for the selected plan
            foreach ($features as $feature) {
                TenantFeature::enableFeature($tenant->id, $feature);
            }
            
            // Disable features not in the plan
            $allFeatures = ['correspondence', 'maintenance_requests', 'visitor_management', 'accounting', 'reservations', 'announcements', 'documents', 'support_tickets', 'payment_agreements', 'voting'];
            
            foreach ($allFeatures as $feature) {
                if (!in_array($feature, $features)) {
                    TenantFeature::disableFeature($tenant->id, $feature);
                }
            }
        }
        
        $this->command->info('Tenant features seeded successfully!');
    }
}