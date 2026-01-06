<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * Custom PersonalAccessToken that supports multi-tenant token lookup.
 *
 * This model searches for tokens in both:
 * 1. The current tenant database (for User tokens)
 * 2. The central database (for SecurityPersonnel tokens)
 *
 * This allows both regular users (tenant-scoped) and security personnel
 * (centrally managed) to authenticate with their tokens.
 */
class CentralPersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * The database connection that should be used by the model.
     * Default to central, but findToken() will also check tenant DB.
     *
     * @var string
     */
    protected $connection = 'central';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'personal_access_tokens';

    /**
     * Find the token instance matching the given token.
     *
     * This method searches in both tenant and central databases to support:
     * - User tokens stored in tenant databases
     * - SecurityPersonnel tokens stored in central database
     *
     * @param  string  $token
     * @return static|null
     */
    public static function findToken($token)
    {
        $hash = hash('sha256', $token);

        // 🔥 DEBUG LOGGING - REMOVE AFTER FIXING AUTH
        \Log::info('CentralPersonalAccessToken::findToken called', [
            'tenancy_initialized' => tenancy()->initialized,
            'tenant_id' => tenancy()->initialized ? tenant('id') : null,
            'token_hash_prefix' => substr($hash, 0, 16) . '...',
        ]);

        // First, check if we're in a tenant context
        if (tenancy()->initialized) {
            // Search in tenant database first (for User tokens)
            // Use TenantPersonalAccessToken which uses the tenant connection
            $tenantToken = TenantPersonalAccessToken::where('token', $hash)->first();

            \Log::info('CentralPersonalAccessToken::findToken tenant search', [
                'token_found' => $tenantToken !== null,
                'token_id' => $tenantToken?->id,
            ]);

            if ($tenantToken) {
                $tokenable = $tenantToken->tokenable;
                \Log::info('CentralPersonalAccessToken::findToken tokenable', [
                    'tokenable_type' => $tenantToken->tokenable_type,
                    'tokenable_id' => $tenantToken->tokenable_id,
                    'tokenable_resolved' => $tokenable !== null,
                    'tokenable_name' => $tokenable?->name ?? 'N/A',
                ]);

                // Return the tenant token - it will properly resolve tokenable
                // because it uses the tenant database connection
                return $tenantToken;
            }
        }

        // Fall back to central database (for SecurityPersonnel tokens)
        $centralToken = static::where('token', $hash)->first();

        \Log::info('CentralPersonalAccessToken::findToken central fallback', [
            'token_found' => $centralToken !== null,
        ]);

        return $centralToken;
    }
}
