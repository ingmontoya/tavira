<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

/**
 * PersonalAccessToken for tenant databases.
 *
 * This model uses the tenant database connection (default when in tenant context)
 * and properly resolves tokenable relationships to tenant users.
 */
class TenantPersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'personal_access_tokens';

    // No $connection specified - will use the default connection
    // which is 'tenant' when in tenant context
}
