<?php

namespace App\Console\Commands;

use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\EmailSettingsSeeder;
use Database\Seeders\InvoiceEmailSettingsSeeder;
use Database\Seeders\PaymentConceptAccountMappingSeeder;
use Database\Seeders\PaymentConceptSeeder;
use Database\Seeders\PaymentMethodAccountMappingSeeder;
use Database\Seeders\PaymentStatusSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SecuritySettingsSeeder;
use Illuminate\Console\Command;

class SetupProductionData extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'app:setup-production {--force : Force setup even if data exists}';

    /**
     * The console command description.
     */
    protected $description = 'Setup production data without Faker dependencies';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Setting up production data...');

        $seeders = [
            SecuritySettingsSeeder::class => 'Security settings',
            RolePermissionSeeder::class => 'Roles and permissions',
            PaymentStatusSeeder::class => 'Payment statuses',
            PaymentConceptSeeder::class => 'Payment concepts',
            EmailSettingsSeeder::class => 'Email settings',
            InvoiceEmailSettingsSeeder::class => 'Invoice email settings',
            ChartOfAccountsSeeder::class => 'Chart of accounts',
            PaymentConceptAccountMappingSeeder::class => 'Payment concept mappings',
            PaymentMethodAccountMappingSeeder::class => 'Payment method mappings',
        ];

        foreach ($seeders as $seederClass => $description) {
            $this->info("Seeding: {$description}");
            
            try {
                $this->call('db:seed', [
                    '--class' => $seederClass,
                    '--force' => $this->option('force'),
                ]);
                $this->line("✅ {$description} seeded successfully");
            } catch (\Exception $e) {
                $this->error("❌ Failed to seed {$description}: " . $e->getMessage());
                return Command::FAILURE;
            }
        }

        $this->newLine();
        $this->info('🎉 Production setup completed successfully!');
        $this->info('Your application now has all the necessary data to function properly.');
        
        $this->newLine();
        $this->info('Next steps:');
        $this->line('1. Create your conjunto configuration via the web interface');
        $this->line('2. Set up apartment types and apartments');
        $this->line('3. Configure your email settings');
        $this->line('4. Create admin users');

        return Command::SUCCESS;
    }
}