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
use App\Notifications\TenantCredentialsCreated;

class TenantManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware(middleware: 'role:superadmin')->except(['create', 'store', 'index', 'show', 'edit', 'update']);
        $this->middleware('role:admin|superadmin')->only(['create', 'store', 'index', 'show', 'edit', 'update']);

        // Restrict tenant management actions to superadmin only
        $this->middleware('role:superadmin')->only(['destroy', 'activate', 'suspend', 'impersonate']);
    }

    private function getTenantData(Tenant $tenant): array
    {
        // Get raw data directly from database to handle casting issues with stancl/tenancy
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
                    // Use PostgreSQL JSON syntax instead of MySQL whereJsonContains
                    $subQuery->whereRaw('data->>? = ?', ['created_by', $user->id])
                        ->orWhereRaw('data->>? = ?', ['created_by_email', $user->email])
                        ->orWhereRaw('data->>? = ?', ['email', $user->email]);
                });
            })
            ->when($request->search, function ($query, $search) {
                $query->where('id', 'like', "%{$search}%")
                    ->orWhereRaw('data->>? ILIKE ?', ['name', "%{$search}%"])
                    ->orWhereRaw('data->>? ILIKE ?', ['email', "%{$search}%"]);
            })
            ->when($request->status && $request->status !== 'all', function ($query, $status) {
                $query->whereRaw('data->>? = ?', ['status', $status]);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($tenant) {
                // Get raw data directly from database to handle casting issues
                $rawData = \DB::table('tenants')->where('id', $tenant->id)->value('data');
                $data = $rawData ? json_decode($rawData, true) : [];
                return [
                    'id' => $tenant->id,
                    // Read from the correct model fields
                    'name' => $tenant->admin_name ?? 'Sin nombre',
                    'email' => $tenant->admin_email ?? null,
                    'status' => $tenant->subscription_status ?? 'pending',
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

            // Check if user just created this tenant (from session)
            $justCreated = session('tenant_id') === $tenant->id && session('tenant_creation_success');

            $canAccess = $justCreated ||
                ($data['created_by'] ?? null) == $user->id ||
                ($data['created_by_email'] ?? null) == $user->email ||
                ($data['email'] ?? null) == $user->email ||
                // Also check tenant admin fields for immediate access after creation
                $tenant->admin_email === $user->email;

            if (!$canAccess) {
                abort(403, 'No tienes acceso a este tenant');
            }
        }

        $data = $this->getTenantData($tenant);
        $tenantData = [
            'id' => $tenant->id,
            // Read from the correct model fields (not JSON data field)
            'name' => $tenant->admin_name ?? 'Sin nombre',
            'email' => $tenant->admin_email ?? null,
            'status' => $tenant->subscription_status ?? 'pending',
            'created_at' => $tenant->created_at->format('d/m/Y H:i'),
            'updated_at' => $tenant->updated_at->format('d/m/Y H:i'),
            'subscription_plan' => $tenant->subscription_plan,
            'subscription_expires_at' => $tenant->subscription_expires_at?->format('d/m/Y H:i'),
            'domains' => $tenant->domains->map(function ($domain) {
                return [
                    'id' => $domain->id,
                    'domain' => $domain->domain,
                    'is_primary' => $domain->is_primary ?? false,
                ];
            }),
            'raw_data' => $data, // Keep for debugging/access control
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

        // Generate a secure random password for the tenant admin
        $tempPassword = $this->generateSecurePassword();
        $subscriptionExpiresAt = now()->addMonth();

        // Create the tenant with custom attributes in data field (stancl/tenancy way)
        $tenantId = Str::uuid();
        $tenant = Tenant::create([
            'id' => $tenantId,
            // Custom attributes automatically go to data field
            'name' => $request->name,
            'email' => $request->email,
            'status' => 'active',
            'created_by' => $user->id,
            'created_by_email' => $user->email,
            'temp_password' => $tempPassword,
            'requires_password_change' => true,
            'admin_created' => true,
            // Fields that go to actual columns
            'admin_name' => $request->name,
            'admin_email' => $request->email,
            'admin_password' => Hash::make($tempPassword),
            'subscription_status' => 'active',
            'subscription_plan' => 'monthly',
            'subscription_expires_at' => $subscriptionExpiresAt,
            'subscription_renewed_at' => now(),
            'subscription_last_checked_at' => now(),
        ]);

        // Create domain
        $tenant->domains()->create([
            'domain' => $request->domain,
        ]);

        // Update user to require subscription and associate with tenant
        $user->update([
            'tenant_id' => $tenant->id,
            'requires_subscription' => true,
            'subscription_required_at' => now(),
        ]);

        // Wait for pipeline to complete and then update data field with important information
        try {
            \Log::info('Starting data field update for tenant: ' . $tenant->id);
            sleep(1); // Reduced sleep to prevent timeouts
            \Log::info('Pipeline wait completed for tenant: ' . $tenant->id);

            // Update the data field directly via database since Eloquent casting has issues with stancl/tenancy
            $currentDataRaw = DB::table('tenants')->where('id', $tenant->id)->value('data');
            $currentData = $currentDataRaw ? json_decode($currentDataRaw, true) : [];

            $updatedData = array_merge($currentData, [
                'name' => $request->name,
                'email' => $request->email,
                'status' => 'active',
                'created_by' => $user->id,
                'created_by_email' => $user->email,
                'created_at' => now()->toISOString(),
                'temp_password' => $tempPassword,
                'debug_password' => $tempPassword,
                'requires_password_change' => true,
                'admin_created' => true,
            ]);

            // Use direct database update for data field to bypass Eloquent casting issues
            \Log::info('Updating tenant data field', ['tenant_id' => $tenant->id, 'data' => $updatedData]);
            DB::table('tenants')->where('id', $tenant->id)->update([
                'data' => json_encode($updatedData)
            ]);
            \Log::info('Tenant data field updated successfully for: ' . $tenant->id);
        } catch (\Exception $dataUpdateError) {
            \Log::error('Failed to update tenant data field', [
                'tenant_id' => $tenant->id,
                'error' => $dataUpdateError->getMessage(),
                'temp_password' => $tempPassword,
            ]);
        }

        // Create tenant admin user in tenant database after pipeline completion
        $adminUserId = null;
        try {

            $tenant->run(function () use (&$adminUserId, $request, $tempPassword) {
                // Create the admin user in the tenant database
                $tenantUser = \App\Models\User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'password' => Hash::make($tempPassword),
                    'email_verified_at' => now(),
                    // Note: tenant users don't have requires_subscription field
                ]);

                // Assign admin role
                if (class_exists(\Spatie\Permission\Models\Role::class)) {
                    $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'superadmin']);
                    $tenantUser->assignRole($adminRole);
                }

                $adminUserId = $tenantUser->id;
            });

            // Update tenant with admin user ID
            if ($adminUserId) {
                $tenant->update(['admin_user_id' => $adminUserId]);
            }

            // Send credentials email
            try {
                $tenantDomain = $tenant->domains->first()->domain ?? null;
                if ($tenantDomain) {
                    $user->notify(new TenantCredentialsCreated($tenant, $tempPassword, $tenantDomain));
                }
            } catch (\Exception $emailError) {
                // Email failed but continue
            }
        } catch (\Exception $e) {
            // Admin user creation failed but continue - tenant is still created
        }

        // Redirect to tenant details with success message
        return redirect()->route('tenant-management.show', $tenant)
            ->with('success', 'Conjunto creado exitosamente. Las credenciales de acceso se enviarán por correo electrónico una vez completada la configuración.')
            ->with('email_sent', true)
            ->with('temp_password', $tempPassword); // Show password temporarily for debugging
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
            // Read from the correct model fields
            'name' => $tenant->admin_name ?? '',
            'email' => $tenant->admin_email ?? '',
            'status' => $tenant->subscription_status ?? 'pending',
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

        // Update both model fields and data field for compatibility
        $tenant->update([
            'admin_name' => $request->name,
            'admin_email' => $request->email,
            'subscription_status' => $request->status,
        ]);

        // Also update data field for backward compatibility and access control
        $data = $tenant->data ?? [];
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

    /**
     * Generate a secure random password that meets system requirements
     */
    private function generateSecurePassword(): string
    {
        // Password requirements: at least 8 characters, with uppercase, lowercase, number, and special character
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()-_=+[]{}|;:,.<>?';

        // Ensure at least one character from each required set
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        // Fill remaining characters randomly
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < 12; $i++) { // Make it 12 characters total
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle the password to randomize character positions
        return str_shuffle($password);
    }
}
