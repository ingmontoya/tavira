<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class TenantManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:superadmin')->except(['create', 'store', 'index', 'show', 'edit', 'update']);
        $this->middleware('role:admin|superadmin')->only(['create', 'store', 'index', 'show', 'edit', 'update']);
    }

    private function getTenantData(Tenant $tenant): array
    {
        // Get raw data directly from database to handle casting issues
        $rawData = DB::table('tenants')->where('id', $tenant->id)->value('data');
        return $rawData ? json_decode($rawData, true) : [];
    }

    public function index(Request $request)
    {
        $user = $request->user();

        $tenants = Tenant::query()
            ->when(!$user->hasRole('superadmin'), function ($query) use ($user) {
                // Filter tenants created by this user or where user is the admin
                $query->where(function ($subQuery) use ($user) {
                    $subQuery->whereJsonContains('data->created_by', $user->id)
                        ->orWhereJsonContains('data->created_by_email', $user->email)
                        ->orWhereJsonContains('data->email', $user->email);
                });
            })
            ->when($request->search, function ($query, $search) {
                $query->where('id', 'like', "%{$search}%")
                    ->orWhereJsonContains('data->name', $search)
                    ->orWhereJsonContains('data->email', $search);
            })
            ->when($request->status && $request->status !== 'all', function ($query, $status) {
                $query->whereJsonContains('data->status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($tenant) {
                // Get raw data directly from database to handle casting issues
                $rawData = \DB::table('tenants')->where('id', $tenant->id)->value('data');
                $data = $rawData ? json_decode($rawData, true) : [];
                return [
                    'id' => $tenant->id,
                    'name' => $data['name'] ?? 'Sin nombre',
                    'email' => $data['email'] ?? null,
                    'status' => $data['status'] ?? 'pending',
                    'created_at' => $tenant->created_at->format('d/m/Y H:i'),
                    'updated_at' => $tenant->updated_at->format('d/m/Y H:i'),
                    'domains' => $tenant->domains->pluck('domain')->toArray(),
                ];
            });

        return Inertia::render('TenantManagement/Index', [
            'tenants' => $tenants,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(Tenant $tenant)
    {
        $user = auth()->user();

        // Ensure user can only access their own tenants (unless superadmin)
        if (!$user->hasRole('superadmin')) {
            $data = $this->getTenantData($tenant);
            $canAccess = ($data['created_by'] ?? null) == $user->id ||
                ($data['created_by_email'] ?? null) == $user->email ||
                ($data['email'] ?? null) == $user->email;

            if (!$canAccess) {
                abort(403, 'No tienes acceso a este tenant');
            }
        }

        $data = $this->getTenantData($tenant);
        $tenantData = [
            'id' => $tenant->id,
            'name' => $data['name'] ?? 'Sin nombre',
            'email' => $data['email'] ?? null,
            'status' => $data['status'] ?? 'pending',
            'created_at' => $tenant->created_at->format('d/m/Y H:i'),
            'updated_at' => $tenant->updated_at->format('d/m/Y H:i'),
            'domains' => $tenant->domains->map(function ($domain) {
                return [
                    'id' => $domain->id,
                    'domain' => $domain->domain,
                    'is_primary' => $domain->is_primary ?? false,
                ];
            }),
            'raw_data' => $data,
        ];

        return Inertia::render('TenantManagement/Show', [
            'tenant' => $tenantData,
        ]);
    }

    public function create()
    {
        return Inertia::render('TenantManagement/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,data->email',
            'domain' => 'required|string|max:255|unique:domains,domain',
        ]);

        $user = auth()->user();

        // Create tenant with minimal data first
        $tenantId = Str::uuid();
        $tenant = Tenant::create([
            'id' => $tenantId,
        ]);

        // Update with full data using direct SQL (workaround for ORM caching issues)
        $tenantData = [
            'name' => $request->name,
            'email' => $request->email,
            'status' => 'pending',
            'created_by' => $user->id,
            'created_by_email' => $user->email,
        ];

        \DB::table('tenants')
            ->where('id', $tenantId)
            ->update(['data' => json_encode($tenantData)]);

        $tenant->domains()->create([
            'domain' => $request->domain,
        ]);

        // For admin users, redirect to central dashboard
        if ($user->hasRole('admin')) {
            return redirect()->route('dashboard')
                ->with('success', 'Conjunto creado exitosamente. Puedes activarlo cuando esté listo para usar.');
        }

        return redirect()->route('tenant-management.show', $tenant)
            ->with('success', 'Tenant creado exitosamente');
    }

    public function edit(Tenant $tenant)
    {
        $user = auth()->user();

        // Ensure user can only edit their own tenants (unless superadmin)
        if (!$user->hasRole('superadmin')) {
            $data = $this->getTenantData($tenant);
            $canAccess = ($data['created_by'] ?? null) == $user->id ||
                ($data['created_by_email'] ?? null) == $user->email ||
                ($data['email'] ?? null) == $user->email;

            if (!$canAccess) {
                abort(403, 'No tienes acceso a este tenant');
            }
        }

        $tenantData = [
            'id' => $tenant->id,
            'name' => $tenant->data['name'] ?? '',
            'email' => $tenant->data['email'] ?? '',
            'status' => $tenant->data['status'] ?? 'pending',
            'domains' => $tenant->domains->map(function ($domain) {
                return [
                    'id' => $domain->id,
                    'domain' => $domain->domain,
                    'is_primary' => $domain->is_primary ?? false,
                ];
            }),
        ];

        return Inertia::render('TenantManagement/Edit', [
            'tenant' => $tenantData,
        ]);
    }

    public function update(Request $request, Tenant $tenant)
    {
        $user = auth()->user();

        // Ensure user can only update their own tenants (unless superadmin)
        if (!$user->hasRole('superadmin')) {
            $data = $this->getTenantData($tenant);
            $canAccess = ($data['created_by'] ?? null) == $user->id ||
                ($data['created_by_email'] ?? null) == $user->email ||
                ($data['email'] ?? null) == $user->email;

            if (!$canAccess) {
                abort(403, 'No tienes acceso a este tenant');
            }
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:tenants,data->email,' . $tenant->id,
            'status' => 'required|in:pending,active,suspended',
        ]);

        $data = $tenant->data;
        $data['name'] = $request->name;
        $data['email'] = $request->email;
        $data['status'] = $request->status;
        $data['updated_by'] = auth()->id();

        $tenant->update(['data' => $data]);

        return redirect()->route('tenant-management.show', $tenant)
            ->with('success', 'Tenant actualizado exitosamente');
    }

    public function destroy(Tenant $tenant)
    {
        $tenant->delete();

        return redirect()->route('tenant-management.index')
            ->with('success', 'Tenant eliminado exitosamente');
    }

    public function impersonate(Tenant $tenant)
    {
        $data = $this->getTenantData($tenant);
        if (($data['status'] ?? 'pending') !== 'active') {
            return back()->with('error', 'Solo puedes acceder a tenants activos');
        }

        $domain = $tenant->domains->first();
        if (!$domain) {
            return back()->with('error', 'El tenant no tiene dominio configurado');
        }

        // Simple redirect to tenant login
        $tenantUrl = (app()->environment('local') ? 'http://' : 'https://') . $domain->domain;
        if (app()->environment('local')) {
            $tenantUrl .= ':8000';
        }
        $loginUrl = $tenantUrl . '/login';

        return Inertia::location($loginUrl);
    }

    public function suspend(Tenant $tenant)
    {
        $data = $tenant->data;
        $data['status'] = 'suspended';
        $data['suspended_by'] = auth()->id();
        $data['suspended_at'] = now()->toISOString();

        $tenant->update(['data' => $data]);

        return back()->with('success', 'Tenant suspendido exitosamente');
    }

    public function activate(Tenant $tenant)
    {
        $data = $tenant->data;
        $data['status'] = 'active';
        $data['activated_by'] = auth()->id();
        $data['activated_at'] = now()->toISOString();

        $tenant->update(['data' => $data]);

        return back()->with('success', 'Tenant activado exitosamente');
    }


}
