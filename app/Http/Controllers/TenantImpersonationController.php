<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Inertia\Inertia;
use Stancl\Tenancy\Features\UserImpersonation;

class TenantImpersonationController extends Controller
{
    /**
     * Handle impersonation using stancl/tenancy official implementation
     */
    public function handleImpersonation($token)
    {
        return UserImpersonation::makeResponse($token);
    }

    /**
     * Generate impersonation token and redirect to tenant
     */
    public function impersonate(Tenant $tenant, $userId, $redirectUrl = '/dashboard')
    {
        // Generate impersonation token using official package method
        $token = tenancy()->impersonate($tenant, $userId, $redirectUrl);

        // Get tenant's domain for redirect
        $domain = $tenant->domains()->first();

        if (! $domain) {
            return redirect()->back()->with('error', 'El tenant no tiene dominio configurado');
        }

        // Redirect to tenant's impersonation endpoint
        $protocol = app()->environment('local') ? 'http://' : 'https://';
        $impersonationUrl = $protocol.$domain->domain."/impersonate/{$token->token}";

        return Inertia::location($impersonationUrl);
    }

    /**
     * Impersonate the admin user in a tenant using stored admin_user_id
     */
    public function impersonateAdmin(Tenant $tenant, $redirectUrl = '/dashboard')
    {
        if (! $tenant->admin_user_id) {
            return redirect()->back()->with('error', 'El tenant no tiene un admin_user_id configurado');
        }

        return $this->impersonate($tenant, $tenant->admin_user_id, $redirectUrl);
    }
}
