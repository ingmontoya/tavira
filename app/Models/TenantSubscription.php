<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantSubscription extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'plan_name',
        'amount',
        'payment_method',
        'payment_reference',
        'transaction_id',
        'status',
        'paid_at',
        'expires_at',
        'payment_data',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'payment_data' => 'array',
    ];

    /**
     * Get the tenant that owns the subscription
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    /**
     * Get the user that owns the subscription
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if subscription is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               ($this->expires_at === null || $this->expires_at->isFuture());
    }

    /**
     * Check if subscription is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Get plan display name
     */
    public function getPlanDisplayName(): string
    {
        $names = [
            'BASICO' => 'Plan Básico',
            'PROFESIONAL' => 'Plan Profesional',
            'EMPRESARIAL' => 'Plan Empresarial',
            'ANUAL_BASICO' => 'Plan Básico Anual',
            'ANUAL_PROFESIONAL' => 'Plan Profesional Anual',
            'ANUAL_EMPRESARIAL' => 'Plan Empresarial Anual',
        ];

        return $names[$this->plan_name] ?? $this->plan_name;
    }

    /**
     * Get billing type
     */
    public function getBillingType(): string
    {
        return str_starts_with($this->plan_name, 'ANUAL_') ? 'anual' : 'mensual';
    }

    /**
     * Scope for active subscriptions
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for expired subscriptions
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->where('status', 'active');
    }
}
