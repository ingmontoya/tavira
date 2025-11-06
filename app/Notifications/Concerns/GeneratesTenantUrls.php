<?php

namespace App\Notifications\Concerns;

use Illuminate\Support\Facades\URL;

trait GeneratesTenantUrls
{
    /**
     * Generate a route URL that respects the tenant context.
     *
     * In tenant context, this will use the tenant's domain.
     * In central context, this will use the APP_URL.
     */
    protected function tenantRoute(string $name, mixed $parameters = [], bool $absolute = true): string
    {
        // Check if we're in a tenant context
        if (tenancy()->initialized) {
            $tenant = tenant();

            // Get the tenant's primary domain
            $domain = $tenant->domains()->first();

            if ($domain) {
                // Get the current request scheme (http or https)
                $scheme = request()->getScheme() ?? 'https';

                // Build the full domain URL
                $tenantUrl = "{$scheme}://{$domain->domain}";

                // Temporarily set the URL root to the tenant domain
                $previousUrl = URL::to('/');
                URL::forceRootUrl($tenantUrl);

                // Generate the route
                $url = route($name, $parameters, $absolute);

                // Restore the previous URL root
                URL::forceRootUrl($previousUrl);

                return $url;
            }
        }

        // Fallback to normal route generation for central context
        return route($name, $parameters, $absolute);
    }
}
