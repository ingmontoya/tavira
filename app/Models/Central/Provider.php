<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Provider extends Model
{
    use CentralConnection, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'category',
        'phone',
        'email',
        'address',
        'document_type',
        'document_number',
        'city',
        'country',
        'contact_name',
        'contact_phone',
        'contact_email',
        'notes',
        'tax_regime',
        'is_active',
        'subscription_plan',
        'subscription_started_at',
        'subscription_expires_at',
        'has_seen_pricing',
        'commission_rate',
        'leads_remaining',
        'leads_used_this_month',
        'has_b2b2c_access',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'subscription_started_at' => 'datetime',
        'subscription_expires_at' => 'datetime',
        'has_seen_pricing' => 'boolean',
        'commission_rate' => 'decimal:2',
        'has_b2b2c_access' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function categories()
    {
        return $this->belongsToMany(
            ProviderCategory::class,
            'provider_category_provider',
            'provider_id',
            'provider_category_id'
        )->withTimestamps();
    }

    public function services()
    {
        return $this->hasMany(ProviderService::class);
    }

    public function users()
    {
        return $this->hasMany(\App\Models\User::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    /**
     * Check if provider has an active subscription plan.
     */
    public function hasActivePlan(): bool
    {
        if ($this->subscription_plan === 'none') {
            return false;
        }

        if ($this->subscription_expires_at && $this->subscription_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if provider is on a specific plan.
     */
    public function isOnPlan(string $plan): bool
    {
        return $this->subscription_plan === $plan;
    }

    /**
     * Check if provider has B2B2C access (Premium only).
     */
    public function hasB2B2CAccess(): bool
    {
        return $this->has_b2b2c_access && $this->isOnPlan('premium');
    }

    /**
     * Get remaining leads for the current month.
     */
    public function getRemainingLeads(): int
    {
        if ($this->isOnPlan('premium')) {
            return -1; // Unlimited
        }

        return max(0, $this->leads_remaining - $this->leads_used_this_month);
    }

    /**
     * Check if provider can respond to more quotation requests.
     */
    public function canRespondToQuotation(): bool
    {
        if ($this->isOnPlan('premium')) {
            return true; // Unlimited
        }

        return $this->getRemainingLeads() > 0;
    }
}
