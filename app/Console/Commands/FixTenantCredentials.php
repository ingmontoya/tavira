<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class FixTenantCredentials extends Command
{
    protected $signature = 'tenants:fix-credentials {tenant_id?} {--password=} {--all}';

    protected $description = 'Fix tenant credentials for tenants missing temp_password';

    public function handle()
    {
        $tenantId = $this->argument('tenant_id');
        $customPassword = $this->option('password');
        $fixAll = $this->option('all');

        if ($fixAll) {
            $this->fixAllTenants($customPassword);
        } elseif ($tenantId) {
            $this->fixSingleTenant($tenantId, $customPassword);
        } else {
            $this->listBrokenTenants();
        }
    }

    private function fixAllTenants($customPassword = null)
    {
        $this->info('Searching for tenants without temp_password...');

        $tenants = Tenant::all();
        $fixed = 0;

        foreach ($tenants as $tenant) {
            $rawData = DB::table('tenants')->where('id', $tenant->id)->value('data');
            $data = $rawData ? json_decode($rawData, true) : [];

            if (! isset($data['temp_password']) && $tenant->admin_name && $tenant->admin_email) {
                $this->fixTenantCredentials($tenant, $customPassword);
                $fixed++;
            }
        }

        $this->info("Fixed {$fixed} tenants.");
    }

    private function fixSingleTenant($tenantId, $customPassword = null)
    {
        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant {$tenantId} not found");

            return 1;
        }

        $this->fixTenantCredentials($tenant, $customPassword);
        $this->info("Tenant {$tenantId} fixed successfully.");
    }

    private function fixTenantCredentials(Tenant $tenant, $customPassword = null)
    {
        $tempPassword = $customPassword ?: $this->generateSecurePassword();

        // Update data field
        $currentDataRaw = DB::table('tenants')->where('id', $tenant->id)->value('data');
        $currentData = $currentDataRaw ? json_decode($currentDataRaw, true) : [];

        $updatedData = array_merge($currentData, [
            'name' => $tenant->admin_name,
            'email' => $tenant->admin_email,
            'status' => 'active',
            'temp_password' => $tempPassword,
            'debug_password' => $tempPassword,
            'requires_password_change' => true,
            'admin_created' => true,
            'fixed_by_command' => true,
            'fixed_at' => now()->toISOString(),
        ]);

        DB::table('tenants')->where('id', $tenant->id)->update([
            'data' => json_encode($updatedData),
        ]);

        // Update tenant database user password
        try {
            $tenant->run(function () use ($tenant, $tempPassword) {
                $user = \App\Models\User::where('email', $tenant->admin_email)->first();
                if ($user) {
                    $user->update([
                        'password' => Hash::make($tempPassword),
                        'email_verified_at' => now(),
                    ]);
                }
            });
        } catch (\Exception $e) {
            $this->warn('Could not update tenant database user: '.$e->getMessage());
        }

        $this->line("Tenant: {$tenant->admin_name} ({$tenant->admin_email})");
        $this->line("Password: {$tempPassword}");
        if ($tenant->domains->count() > 0) {
            $this->line('URL: http://'.$tenant->domains->first()->domain.':8000');
        }
        $this->line('---');
    }

    private function listBrokenTenants()
    {
        $this->info('Tenants missing temp_password:');

        $tenants = Tenant::all();
        $broken = 0;

        foreach ($tenants as $tenant) {
            $rawData = DB::table('tenants')->where('id', $tenant->id)->value('data');
            $data = $rawData ? json_decode($rawData, true) : [];

            if (! isset($data['temp_password'])) {
                $this->line("- {$tenant->id}: {$tenant->admin_name} ({$tenant->admin_email})");
                $broken++;
            }
        }

        if ($broken === 0) {
            $this->info('No broken tenants found!');
        } else {
            $this->info("\nFound {$broken} tenants without temp_password.");
            $this->info('Run with --all to fix all, or specify tenant_id to fix one.');
            $this->info('Example: php artisan tenants:fix-credentials --all');
        }
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

        $allChars = $uppercase.$lowercase.$numbers.$special;
        for ($i = 4; $i < 12; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        return str_shuffle($password);
    }
}
