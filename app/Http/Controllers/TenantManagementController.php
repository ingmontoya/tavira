<?php

namespace App\Http\Controllers;

use App\Jobs\CreateTenantAdminUser;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TenantApprovalRequest;

class TenantManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:superadmin')->except(['create', 'store', 'index', 'show', 'edit', 'update']);
        $this->middleware('role:admin|superadmin')->only(['create', 'store', 'index', 'show', 'edit', 'update']);
        
        // Restrict tenant management actions to superadmin only
        $this->middleware('role:superadmin')->only(['destroy', 'activate', 'suspend', 'impersonate']);
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

        // Create tenant with admin user information
        $tenantId = Str::uuid();
        $tenant = Tenant::create([
            'id' => $tenantId,
            'admin_name' => $user->name,
            'admin_email' => $user->email,
            'admin_password' => Hash::make('password123'), // Default password, user should change it
        ]);

        // Update with full data using direct SQL (workaround for ORM caching issues)
        $tenantData = [
            'name' => $request->name,
            'email' => $request->email,
            'status' => 'active', // Create tenants directly as active for automatic provisioning
            'created_by' => $user->id,
            'created_by_email' => $user->email,
            'created_at' => now()->toISOString(),
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
                ->with('success', 'Conjunto creado y configurado exitosamente.');
        }

        return redirect()->route('tenant-management.show', $tenant)
            ->with('success', 'Tenant creado y configurado exitosamente.');
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

    public function impersonate(Request $request, Tenant $tenant)
    {
        $request->validate([
            'user_id' => 'nullable|integer',
            'redirect_url' => 'string|nullable'
        ]);

        $data = $this->getTenantData($tenant);
        if (($data['status'] ?? 'pending') !== 'active') {
            return response()->json(['error' => 'Solo puedes acceder a tenants activos'], 409);
        }

        $domain = $tenant->domains->first();
        if (!$domain) {
            return response()->json(['error' => 'El tenant no tiene dominio configurado'], 409);
        }

        try {
            // Determine user ID to impersonate
            // Priority: request user_id -> stored admin_user_id -> error
            $userId = $request->user_id ?? $tenant->admin_user_id;
            
            if (!$userId) {
                return response()->json([
                    'error' => 'No se puede determinar qué usuario impersonar. ' .
                              'Especifica un user_id o asegúrate de que el tenant tenga un admin_user_id válido.'
                ], 400);
            }

            // Generate impersonation token using stancl/tenancy
            $redirectUrl = $request->redirect_url ?? '/dashboard';
            $tokenObject = tenancy()->impersonate($tenant, $userId, $redirectUrl, 'web');
            
            // Extract the token string from the object
            $token = $tokenObject->token ?? $tokenObject;
            
            // Build the impersonation URL
            $tenantUrl = (app()->environment('local') ? 'http://' : 'https://') . $domain->domain;
            if (app()->environment('local')) {
                $tenantUrl .= ':8001'; // Updated to match current server port
            }
            
            $impersonationUrl = $tenantUrl . '/impersonate/' . $token;

            return response()->json([
                'success' => true,
                'url' => $impersonationUrl,
                'message' => 'Token de impersonación generado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al generar token de impersonación: ' . $e->getMessage()], 500);
        }
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
        $data = $this->getTenantData($tenant);
        
        // Allow reactivation of suspended tenants
        if ($data['status'] === 'active') {
            return back()->with('info', 'El tenant ya está activo.');
        }

        try {
            // Run migrations and seed for tenant
            \Artisan::call('tenants:migrate', ['--tenant' => $tenant->id]);
            \Artisan::call('tenants:seed', ['--tenant' => $tenant->id]);
            
            // Create admin user if credentials are provided
            if ($tenant->admin_name && $tenant->admin_email && $tenant->admin_password) {
                $createUserJob = new CreateTenantAdminUser($tenant);
                $createUserJob->handle();
            }
            
            $data['status'] = 'active';
            $data['activated_by'] = auth()->id();
            $data['activated_at'] = now()->toISOString();

            // Use direct SQL update to ensure data persistence
            \DB::table('tenants')
                ->where('id', $tenant->id)
                ->update(['data' => json_encode($data)]);

            $message = 'Tenant activado exitosamente. Base de datos creada y configurada.';
            if ($tenant->admin_user_id) {
                $message .= ' Usuario administrador creado.';
            }

            return back()->with('success', $message);
        } catch (\Exception $e) {
            return back()->with('error', 'Error al activar el tenant: ' . $e->getMessage());
        }
    }

    public function getUsers(Tenant $tenant)
    {
        $data = $this->getTenantData($tenant);
        if (($data['status'] ?? 'pending') !== 'active') {
            return response()->json(['error' => 'Solo puedes obtener usuarios de tenants activos'], 409);
        }

        try {
            // Initialize tenancy to access tenant database
            tenancy()->initialize($tenant);
            
            // Get users from tenant database
            $users = \App\Models\User::select('id', 'name', 'email', 'created_at')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'created_at' => $user->created_at->format('d/m/Y H:i'),
                    ];
                });

            // End tenancy
            tenancy()->end();

            return response()->json(['users' => $users]);
        } catch (\Exception $e) {
            // Ensure tenancy is ended even if there's an error
            tenancy()->end();
            return response()->json(['error' => 'Error al obtener usuarios del tenant: ' . $e->getMessage()], 500);
        }
    }


}
