<?php

namespace App\Observers;

use App\Jobs\InitializeTenantAccounting;
use App\Models\ConjuntoConfig;

class ConjuntoConfigObserver
{
    /**
     * Handle the ConjuntoConfig "created" event.
     *
     * When a new ConjuntoConfig is created, automatically initialize
     * the accounting chart of accounts and payment method mappings for it.
     */
    public function created(ConjuntoConfig $conjuntoConfig): void
    {
        // Get current tenant
        $tenant = tenancy()->tenant;

        if ($tenant) {
            // Dispatch job to initialize accounting for this tenant
            // This will seed chart of accounts and create payment method mappings
            InitializeTenantAccounting::dispatch($tenant);
        }
    }
}
