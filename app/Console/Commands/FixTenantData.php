<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class FixTenantData extends Command
{
    protected $signature = 'tenants:fix-data {tenant_id} {name} {email}';
    protected $description = 'Fix tenant data for a specific tenant';

    public function handle()
    {
        $tenantId = $this->argument('tenant_id');
        $name = $this->argument('name');
        $email = $this->argument('email');

        $tenant = Tenant::find($tenantId);
        
        if (!$tenant) {
            $this->error("Tenant {$tenantId} not found");
            return 1;
        }

        // Generate a new password for testing
        $tempPassword = $this->generateSecurePassword();

        $tenantData = [
            'name' => $name,
            'email' => $email,
            'status' => 'active',
            'created_by' => auth()->id() ?? 1,
            'created_by_email' => $email,
            'created_at' => now()->toISOString(),
            'temp_password' => $tempPassword,
            'debug_password' => $tempPassword,
            'requires_password_change' => true,
            'admin_created' => true,
            'fixed_at' => now()->toISOString(),
        ];

        // Update tenant data directly
        DB::table('tenants')
            ->where('id', $tenantId)
            ->update([
                'data' => json_encode($tenantData),
                'admin_name' => $name,
                'admin_email' => $email,
                'subscription_status' => 'active',
                'subscription_plan' => 'monthly',
                'subscription_expires_at' => now()->addMonth(),
                'subscription_renewed_at' => now(),
                'subscription_last_checked_at' => now(),
            ]);

        $this->info("Tenant {$tenantId} updated successfully");
        $this->info("Name: {$name}");
        $this->info("Email: {$email}");
        $this->info("Temp Password: {$tempPassword}");

        return 0;
    }

    private function generateSecurePassword(): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()-_=+[]{}|;:,.<>?';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < 12; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        return str_shuffle($password);
    }
}