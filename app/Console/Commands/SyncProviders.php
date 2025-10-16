<?php

namespace App\Console\Commands;

use App\Models\Central\Provider as CentralProvider;
use App\Models\Provider;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncProviders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:providers
                            {--tenant= : Sync only to specific tenant ID}
                            {--force : Force sync even if provider already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync providers from central database to all tenant databases';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting provider synchronization...');

        try {
            // Get central providers
            $centralProviders = CentralProvider::on('central')->get();

            if ($centralProviders->isEmpty()) {
                $this->warn('No providers found in central database.');

                return self::SUCCESS;
            }

            $this->info("Found {$centralProviders->count()} providers in central database.");

            // Get tenants to sync
            $tenants = $this->option('tenant')
                ? Tenant::where('id', $this->option('tenant'))->get()
                : Tenant::all();

            if ($tenants->isEmpty()) {
                $this->warn('No tenants found to sync.');

                return self::SUCCESS;
            }

            $this->info("Syncing to {$tenants->count()} tenant(s)...");

            $progressBar = $this->output->createProgressBar($tenants->count());
            $progressBar->start();

            $totalSynced = 0;
            $totalSkipped = 0;
            $errors = [];

            foreach ($tenants as $tenant) {
                try {
                    $tenant->run(function () use ($centralProviders, &$totalSynced, &$totalSkipped) {
                        foreach ($centralProviders as $centralProvider) {
                            $result = Provider::updateOrCreate(
                                ['global_provider_id' => $centralProvider->id],
                                [
                                    'name' => $centralProvider->name,
                                    'category' => $centralProvider->category,
                                    'phone' => $centralProvider->phone,
                                    'email' => $centralProvider->email,
                                    'address' => $centralProvider->address,
                                    'document_type' => $centralProvider->document_type,
                                    'document_number' => $centralProvider->document_number,
                                    'city' => $centralProvider->city,
                                    'country' => $centralProvider->country,
                                    'contact_name' => $centralProvider->contact_name,
                                    'contact_phone' => $centralProvider->contact_phone,
                                    'contact_email' => $centralProvider->contact_email,
                                    'notes' => $centralProvider->notes,
                                    'tax_regime' => $centralProvider->tax_regime,
                                    'is_active' => $centralProvider->is_active,
                                ]
                            );

                            if ($result->wasRecentlyCreated) {
                                $totalSynced++;
                            } elseif ($result->wasChanged()) {
                                $totalSynced++;
                            } else {
                                $totalSkipped++;
                            }
                        }
                    });
                } catch (\Exception $e) {
                    $errors[] = "Tenant {$tenant->id}: {$e->getMessage()}";
                }

                $progressBar->advance();
            }

            $progressBar->finish();
            $this->newLine(2);

            // Display summary
            $this->info('Synchronization completed!');
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Central Providers', $centralProviders->count()],
                    ['Tenants Processed', $tenants->count()],
                    ['Records Synced/Updated', $totalSynced],
                    ['Records Skipped (no changes)', $totalSkipped],
                    ['Errors', count($errors)],
                ]
            );

            if (! empty($errors)) {
                $this->error('The following errors occurred:');
                foreach ($errors as $error) {
                    $this->error("  - {$error}");
                }

                return self::FAILURE;
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed to sync providers: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
