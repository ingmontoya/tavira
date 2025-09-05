<?php

namespace App\Models;

use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant implements TenantWithDatabase
{
    use HasDatabase, HasDomains;

    protected $casts = [
        'data' => 'array',
        'subscription_expires_at' => 'datetime',
        'subscription_renewed_at' => 'datetime',
        'subscription_last_checked_at' => 'datetime',
    ];

    protected $fillable = [
        'id',
        'data',
        'admin_name',
        'admin_email',
        'admin_password',
        'admin_user_id',
        'subscription_status',
        'subscription_plan',
        'subscription_expires_at',
        'subscription_renewed_at',
        'subscription_last_checked_at',
    ];

    protected $hidden = [
        'admin_password',
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'admin_name',
            'admin_email',
            'admin_password',
            'admin_user_id',
        ];
    }
}
