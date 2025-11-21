<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Get the base URL for Ziggy route generation.
     * In tenant context, use the tenant's domain. Otherwise use APP_URL.
     */
    protected function getBaseUrl(Request $request): string
    {
        // Get the current host
        $host = $request->getHost();

        // List of central domains that should use APP_URL
        $centralDomains = ['127.0.0.1', 'localhost', 'tavira.com.co', 'staging.tavira.com.co'];

        // If we're on a central domain, use APP_URL
        if (in_array($host, $centralDomains)) {
            return config('app.url') ?: $request->getSchemeAndHttpHost();
        }

        // For tenant domains, use the current scheme and host
        // This ensures tenant-specific URLs are generated correctly
        return $request->getSchemeAndHttpHost();
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $upcomingMaintenance = null;

        // Get upcoming maintenance notifications for authenticated users
        if ($request->user()) {
            $upcomingMaintenance = \App\Models\MaintenanceRequest::recurringDueSoon(30)
                ->with('maintenanceCategory')
                ->orderBy('next_occurrence_date')
                ->take(5)
                ->get()
                ->map(fn ($maintenance) => [
                    'id' => $maintenance->id,
                    'title' => $maintenance->title,
                    'category' => $maintenance->maintenanceCategory->name,
                    'next_occurrence_date' => $maintenance->next_occurrence_date?->format('Y-m-d'),
                    'days_until' => $maintenance->next_occurrence_date?->diffInDays(now()),
                    'priority' => $maintenance->priority,
                    'location' => $maintenance->location,
                    'recurrence_frequency' => $maintenance->getRecurrenceFrequencyLabel(),
                    'url' => route('maintenance-requests.show', $maintenance),
                ]);
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'is_impersonating' => session('is_impersonating', false),
                'tenant_name' => session('tenant_name'),
                'original_admin_id' => session('original_admin_id'),
            ],
            'upcomingMaintenance' => fn () => $upcomingMaintenance,
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                // Use APP_URL as base for location to ensure correct URL in K8s/proxied environments
                // This prevents Ziggy from using internal pod IPs (127.0.0.1) for route generation
                'location' => rtrim($this->getBaseUrl($request), '/').$request->getRequestUri(),
                // Override URL and port to use APP_URL instead of internal request URL
                // This fixes issues with Ziggy generating 127.0.0.1 URLs in K8s environments
                // For tenants, use the current domain instead of central APP_URL
                'url' => $this->getBaseUrl($request),
                'port' => null,
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
                'warning' => fn () => $request->session()->get('warning'),
            ],
        ];
    }
}
