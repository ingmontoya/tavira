<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CentralDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // For non-superadmin users, only show their own tenants
        $tenantQuery = Tenant::query();

        if (! $user->hasRole('superadmin')) {
            // Filter tenants created by this user or where user is the admin
            $tenantQuery->where(function ($query) use ($user) {
                $query->whereJsonContains('data->created_by', $user->id)
                    ->orWhereJsonContains('data->created_by_email', $user->email)
                    ->orWhereJsonContains('data->email', $user->email);
            });
        }

        // Stats for central management
        $totalTenants = $tenantQuery->count();

        // Count active tenants (those with domains)
        $activeTenants = (clone $tenantQuery)->whereHas('domains')->count();

        // Count pending tenants (those without domains)
        $pendingTenants = (clone $tenantQuery)->whereDoesntHave('domains')->count();

        $stats = [
            'totalTenants' => $totalTenants,
            'activeTenants' => $activeTenants,
            'pendingTenants' => $pendingTenants,
        ];

        // Recent tenants
        $recentTenants = $tenantQuery->with('domains')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($tenant) {
                // Get raw data directly from database to handle casting issues
                $rawData = \DB::table('tenants')->where('id', $tenant->id)->value('data');
                $data = $rawData ? json_decode($rawData, true) : [];

                // Use admin_name as fallback if data['name'] is not available
                $name = $data['name'] ?? $tenant->admin_name ?? 'Sin nombre';

                // Determine status: if tenant has domains, consider it active
                $hasActiveDomain = $tenant->domains()->exists();
                $status = $data['status'] ?? ($hasActiveDomain ? 'active' : 'pending');

                return [
                    'id' => $tenant->id,
                    'name' => $name,
                    'status' => $status,
                    'created_at' => $tenant->created_at->format('d/m/Y'),
                ];
            });

        return Inertia::render('CentralDashboard', [
            'stats' => $stats,
            'recentTenants' => $recentTenants,
            'user' => $user,
        ]);
    }
}
