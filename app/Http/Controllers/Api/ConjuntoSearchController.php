<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ConjuntoConfig;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Public API controller for searching residential complexes (conjuntos)
 * Used by mobile app registration to find and validate tenant information
 */
class ConjuntoSearchController extends Controller
{
    /**
     * Search for conjuntos (tenants) by name
     * Returns a list of matching tenants with their subdomain for selection
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q', '');

        if (strlen($query) < 2) {
            return response()->json([
                'data' => [],
                'message' => 'Query must be at least 2 characters',
            ]);
        }

        // Search tenants by data->name (JSON), admin_name, or domain
        // Only return active tenants with valid subscriptions
        // Using PostgreSQL JSON syntax: data->>'name' extracts as text
        $tenants = Tenant::query()
            ->where(function ($q) use ($query) {
                // Search in the 'data' JSON field for name, admin_name, or by domain
                $q->whereRaw("COALESCE(data->>'name', '') ILIKE ?", ["%{$query}%"])
                    ->orWhere('admin_name', 'ilike', "%{$query}%")
                    ->orWhereHas('domains', function ($dq) use ($query) {
                        $dq->where('domain', 'ilike', "%{$query}%");
                    });
            })
            ->whereIn('subscription_status', ['active', 'trial'])
            ->with('domains')
            ->limit(10)
            ->get();

        $results = $tenants->map(function ($tenant) {
            // Get the primary domain (subdomain)
            $domain = $tenant->domains->first();
            $subdomain = $domain ? str_replace('.tavira.com.co', '', $domain->domain) : $tenant->id;

            return [
                'id' => $tenant->id,
                'name' => $tenant->name ?? $tenant->admin_name ?? $tenant->id,
                'subdomain' => $subdomain,
                'city' => $tenant->city ?? null,
                'address' => $tenant->address ?? null,
            ];
        });

        return response()->json([
            'data' => $results,
        ]);
    }

    /**
     * Get the structure (towers and apartments) for a specific conjunto
     * Called after user selects a conjunto to populate tower/apartment selectors
     */
    public function getStructure(string $subdomain): JsonResponse
    {
        // Find tenant by subdomain
        $domain = Domain::where('domain', $subdomain.'.tavira.com.co')
            ->orWhere('domain', $subdomain)
            ->first();

        if (! $domain) {
            return response()->json([
                'error' => 'Conjunto no encontrado',
            ], 404);
        }

        $tenant = $domain->tenant;

        if (! $tenant || ! in_array($tenant->subscription_status, ['active', 'trial'])) {
            return response()->json([
                'error' => 'Conjunto no disponible',
            ], 404);
        }

        // Run in tenant context to get conjunto config and apartments
        $structure = null;
        $tenant->run(function () use (&$structure) {
            $config = ConjuntoConfig::first();

            if (! $config) {
                $structure = [
                    'towers' => [],
                    'apartments' => [],
                ];

                return;
            }

            // Get unique tower names from apartments or from config
            $towers = Apartment::query()
                ->select('tower')
                ->distinct()
                ->orderBy('tower')
                ->pluck('tower')
                ->toArray();

            // If no apartments exist, use tower names from config
            if (empty($towers) && $config->tower_names) {
                $towers = $config->tower_names;
            } elseif (empty($towers)) {
                // Generate default tower names
                $towers = range(1, $config->number_of_towers ?? 1);
            }

            // Get apartments grouped by tower
            $apartments = Apartment::query()
                ->select('id', 'number', 'tower', 'floor')
                ->whereDoesntHave('residents') // Only show apartments without residents
                ->orderBy('tower')
                ->orderBy('floor')
                ->orderBy('number')
                ->get()
                ->groupBy('tower')
                ->map(function ($apts) {
                    return $apts->map(function ($apt) {
                        return [
                            'id' => $apt->id,
                            'number' => $apt->number,
                            'floor' => $apt->floor,
                        ];
                    })->values();
                });

            $structure = [
                'name' => $config->name,
                'towers' => array_values($towers),
                'apartments' => $apartments,
            ];
        });

        return response()->json([
            'data' => $structure,
        ]);
    }

    /**
     * Validate if a subdomain exists and is active
     */
    public function validateSubdomain(string $subdomain): JsonResponse
    {
        $domain = Domain::where('domain', $subdomain.'.tavira.com.co')
            ->orWhere('domain', $subdomain)
            ->first();

        if (! $domain) {
            return response()->json([
                'valid' => false,
                'message' => 'Conjunto no encontrado',
            ]);
        }

        $tenant = $domain->tenant;

        if (! $tenant || ! in_array($tenant->subscription_status, ['active', 'trial'])) {
            return response()->json([
                'valid' => false,
                'message' => 'Conjunto no disponible para registro',
            ]);
        }

        return response()->json([
            'valid' => true,
            'name' => $tenant->name ?? $tenant->admin_name ?? $tenant->id,
            'subdomain' => $subdomain,
        ]);
    }
}
