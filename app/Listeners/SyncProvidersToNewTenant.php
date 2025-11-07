<?php

namespace App\Listeners;

use App\Models\Central\Provider;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Events\TenantCreated;

class SyncProvidersToNewTenant implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TenantCreated $event): void
    {
        $tenant = $event->tenant;

        Log::info("Syncing providers to newly created tenant: {$tenant->id}");

        try {
            // Initialize tenant context
            $tenant->run(function () use ($tenant) {
                // Get all providers from central database
                // Provider model already uses CentralConnection trait, so it automatically uses 'central' connection
                $centralProviders = Provider::all();

                Log::info("Found {$centralProviders->count()} providers to sync");

                // Sync each provider to the new tenant
                foreach ($centralProviders as $centralProvider) {
                    \App\Models\Provider::updateOrCreate(
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
                }

                Log::info("Successfully synced providers to tenant: {$tenant->id}");
            });
        } catch (\Exception $e) {
            Log::error("Failed to sync providers to tenant {$tenant->id}: {$e->getMessage()}");
            throw $e;
        }
    }
}
