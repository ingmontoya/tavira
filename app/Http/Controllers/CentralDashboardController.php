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
        $activeTenants = (clone $tenantQuery)->where('data->status', 'active')->count();
        $pendingTenants = (clone $tenantQuery)->where('data->status', 'pending')->count();

        $stats = [
            'totalTenants' => $totalTenants,
            'activeTenants' => $activeTenants,
            'pendingTenants' => $pendingTenants,
        ];

        // Recent tenants
        $recentTenants = $tenantQuery->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($tenant) {
                // Get raw data directly from database to handle casting issues
                $rawData = \DB::table('tenants')->where('id', $tenant->id)->value('data');
                $data = $rawData ? json_decode($rawData, true) : [];

                return [
                    'id' => $tenant->id,
                    'name' => $data['name'] ?? 'Sin nombre',
                    'status' => $data['status'] ?? 'pending',
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
