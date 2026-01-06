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
        // Handle tokens with ID|token format (standard Sanctum format)
        if (strpos($token, '|') !== false) {
            [$id, $token] = explode('|', $token, 2);
        } else {
            $id = null;
        }

        $hash = hash('sha256', $token);

        // First, check if we're in a tenant context
        if (tenancy()->initialized) {
            // Search in tenant database first (for User tokens)
            if ($id !== null) {
                // Optimized lookup by ID first, then verify hash
                $tenantToken = TenantPersonalAccessToken::find($id);
                if ($tenantToken && hash_equals($tenantToken->token, $hash)) {
                    return $tenantToken;
                }
            } else {
                // Fallback to hash lookup if no ID provided
                $tenantToken = TenantPersonalAccessToken::where('token', $hash)->first();
                if ($tenantToken) {
                    return $tenantToken;
                }
            }
        }

        // Fall back to central database (for SecurityPersonnel tokens)
        if ($id !== null) {
            // Optimized lookup by ID first, then verify hash
            $centralToken = static::find($id);
            if ($centralToken && hash_equals($centralToken->token, $hash)) {
                return $centralToken;
            }
        } else {
            // Fallback to hash lookup if no ID provided
            return static::where('token', $hash)->first();
        }

        return null;
    }
}
