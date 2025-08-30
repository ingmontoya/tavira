<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class TenantImpersonationController extends Controller
{
    public function handleImpersonation(Request $request)
    {
        $token = $request->get('token');
        
        if (!$token) {
            return redirect('/')->with('error', 'Token de impersonación no válido');
        }

        // Check if we have valid impersonation data in central session
        $impersonationData = $this->validateImpersonationToken($token);
        
        if (!$impersonationData) {
            return redirect('/')->with('error', 'Token de impersonación expirado o no válido');
        }

        // Switch to the target tenant context
        $tenant = Tenant::find($impersonationData['target_tenant_id']);
        
        if (!$tenant) {
            return redirect('/')->with('error', 'Tenant no encontrado');
        }

        // Initialize tenancy for this tenant
        Tenancy::initialize($tenant);

        // Get or create a superadmin user in this tenant
        $adminUser = $this->getOrCreateSuperAdminUser($impersonationData['impersonating_user_id']);

        // Login as this user
        Auth::login($adminUser);

        // Clear the impersonation token
        session()->forget(['impersonation_token', 'impersonating_user_id', 'target_tenant_id']);

        // Store impersonation info for display
        session([
            'is_impersonating' => true,
            'original_admin_id' => $impersonationData['impersonating_user_id'],
            'tenant_name' => $tenant->data['name'] ?? 'Sin nombre',
        ]);

        return redirect('/dashboard')->with('success', 'Acceso exitoso al tenant como superadmin');
    }

    public function stopImpersonation(Request $request)
    {
        if (!session('is_impersonating')) {
            return redirect('/dashboard')->with('error', 'No estás en modo de impersonación');
        }

        // Clear impersonation session
        session()->forget(['is_impersonating', 'original_admin_id', 'tenant_name']);

        // Logout from tenant
        Auth::logout();

        // Redirect to central dashboard
        $centralDomain = config('tenancy.central_domains')[0];
        $centralUrl = (app()->environment('local') ? 'http://' : 'https://') . $centralDomain;
        
        return Inertia::location($centralUrl . '/dashboard');
    }

    private function validateImpersonationToken(string $token): ?array
    {
        // In a real implementation, you might want to store this in Redis or database
        // For now, we'll use a simple session-based approach
        
        // Check if we're on the central domain and have session data
        if (session('impersonation_token') === $token) {
            return [
                'target_tenant_id' => session('target_tenant_id'),
                'impersonating_user_id' => session('impersonating_user_id'),
            ];
        }

        return null;
    }

    private function getOrCreateSuperAdminUser(int $originalAdminId): User
    {
        // Get original admin user data from central database
        $originalAdmin = DB::connection('central')->table('users')->find($originalAdminId);
        
        if (!$originalAdmin) {
            throw new \Exception('Original admin user not found');
        }

        // Ensure superadmin role exists in this tenant database
        $this->ensureSuperAdminRoleExists();

        // Check if user already exists in this tenant
        $existingUser = User::where('email', $originalAdmin->email)->first();
        
        if ($existingUser) {
            // Ensure user has superadmin role
            if (!$existingUser->hasRole('superadmin')) {
                $existingUser->assignRole('superadmin');
            }
            return $existingUser;
        }

        // Create new superadmin user in this tenant
        $user = User::create([
            'name' => $originalAdmin->name . ' (Super Admin)',
            'email' => $originalAdmin->email,
            'password' => $originalAdmin->password, // Same password hash
            'email_verified_at' => now(),
        ]);

        // Assign superadmin role
        $user->assignRole('superadmin');

        return $user;
    }

    private function ensureSuperAdminRoleExists(): void
    {
        // Check if superadmin role exists in this tenant database
        if (!Role::where('name', 'superadmin')->exists()) {
            // Create superadmin role
            $superadmin = Role::create(['name' => 'superadmin', 'guard_name' => 'web']);

            // Create all permissions for full tenant management
            $permissions = [
                'access_dashboard',
                'view_dashboard',
                'manage_users',
                'create_users',
                'view_users',
                'edit_users',
                'delete_users',
                'manage_settings',
                'manage_apartments',
                'view_apartments',
                'manage_residents',
                'view_residents',
                'manage_finance',
                'view_payments',
                'view_account_statement',
                'view_expenses',
                'manage_expense_categories',
                'approve_expenses',
                'view_accounting',
                'view_reservations',
                'manage_reservable_assets',
                'view_correspondence',
                'view_announcements',
                'create_announcements',
                'edit_announcements',
                'invite_visitors',
                'manage_visitors',
                'receive_notifications',
                'send_pqrs',
                'send_messages_to_admin',
                'view_admin_email',
                'view_council_email',
                'manage_email_templates',
                'view_reports',
                'view_access_logs',
                'edit_conjunto_config',
                'view_conjunto_config',
                'manage_invitations',
                'view_maintenance_requests',
                'view_maintenance_categories',
                'view_maintenance_staff',
                'review_provider_proposals',
            ];

            foreach ($permissions as $permissionName) {
                if (!Permission::where('name', $permissionName)->exists()) {
                    Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
                }
            }

            // Assign all permissions to superadmin
            $superadmin->givePermissionTo($permissions);
        }
    }
}