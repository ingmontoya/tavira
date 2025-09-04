<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use Illuminate\Support\Facades\Hash;

class FixProductionTenants extends Command
{
    protected $signature = 'tenants:fix-production {--dry-run : Show what would be fixed without making changes}';
    protected $description = 'Fix tenant login issues and ensure proper setup for production';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->warn('🔍 DRY RUN - No changes will be made');
            $this->line('');
        }
        
        $this->info('🔧 Fixing production tenants...');
        
        $tenants = Tenant::all();
        $fixed = 0;
        $errors = 0;
        
        foreach ($tenants as $tenant) {
            try {
                $this->line("📋 Processing tenant: {$tenant->id}");
                
                // Initialize tenant context
                tenancy()->initialize($tenant);
                
                // Check if admin user exists
                $adminUser = \App\Models\User::where('email', $tenant->admin_email)->first();
                
                if (!$adminUser) {
                    // Create admin user if doesn't exist
                    $tempPassword = $this->generateSecurePassword();
                    
                    if (!$isDryRun) {
                        $adminUser = \App\Models\User::create([
                            'name' => $tenant->admin_name,
                            'email' => $tenant->admin_email,
                            'password' => Hash::make($tempPassword),
                            'email_verified_at' => now(),
                        ]);
                        
                        // Assign admin role
                        if (class_exists(\Spatie\Permission\Models\Role::class)) {
                            $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
                            $adminUser->assignRole($adminRole);
                        }
                    }
                    
                    $this->line("   ✅ Created admin user with password: {$tempPassword}");
                    $fixed++;
                } else {
                    // Reset password for existing user
                    $tempPassword = $this->generateSecurePassword();
                    
                    if (!$isDryRun) {
                        $adminUser->update(['password' => Hash::make($tempPassword)]);
                    }
                    
                    $this->line("   🔑 Reset password to: {$tempPassword}");
                    $this->line("   ⚠️  Password meets security requirements (12 chars, mixed case, numbers, symbols)");
                    $fixed++;
                }
                
                // Update tenant admin_user_id
                if (!$isDryRun && $adminUser) {
                    $tenant->update(['admin_user_id' => $adminUser->id]);
                }
                
                // End tenant context
                tenancy()->end();
                
                // Fix tenant data if missing
                if (!$tenant->data || !is_array($tenant->data)) {
                    $tenantData = [
                        'name' => 'Conjunto ' . $tenant->admin_name,
                        'email' => $tenant->admin_email,
                        'status' => 'active',
                        'created_by' => $adminUser?->id,
                        'created_by_email' => $tenant->admin_email,
                        'created_at' => now()->toISOString(),
                        'temp_password' => $tempPassword,
                        'requires_password_change' => true,
                    ];
                    
                    if (!$isDryRun) {
                        \DB::table('tenants')
                            ->where('id', $tenant->id)
                            ->update(['data' => json_encode($tenantData)]);
                    }
                    
                    $this->line("   📄 Fixed tenant data");
                }
                
            } catch (\Exception $e) {
                $this->error("   ❌ Error processing tenant {$tenant->id}: {$e->getMessage()}");
                $errors++;
                
                // Ensure tenant context is ended
                try {
                    tenancy()->end();
                } catch (\Exception $endError) {
                    // Ignore errors ending tenancy
                }
            }
        }
        
        $this->line('');
        $this->info("✅ Processing completed!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total tenants processed', $tenants->count()],
                ['Tenants fixed', $fixed],
                ['Errors', $errors],
            ]
        );
        
        if ($isDryRun) {
            $this->warn('🔸 This was a dry run. Run without --dry-run to apply changes.');
        } else {
            $this->info('🚀 Run tenants:sync-subscription-status to update subscription states');
        }
        
        return $errors > 0 ? 1 : 0;
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