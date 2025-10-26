<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
        'phone',
        'position',
        'department',
        'is_active',
        'avatar',
        'tenant_id',
        'requires_subscription',
        'subscription_required_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'requires_subscription' => 'boolean',
            'subscription_required_at' => 'datetime',
        ];
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Get the resident associated with this user based on email matching.
     */
    public function resident()
    {
        return $this->hasOne(Resident::class, 'email', 'email');
    }

    /**
     * Get all residents associated with this user based on email matching.
     */
    public function residents()
    {
        return $this->hasMany(Resident::class, 'email', 'email');
    }

    /**
     * Get the apartment through the resident relationship.
     */
    public function apartment()
    {
        return $this->hasOneThrough(Apartment::class, Resident::class, 'email', 'id', 'email', 'apartment_id');
    }

    /**
     * Scope to get only active users.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get only administrative users.
     */
    public function scopeAdministrative($query)
    {
        return $query->whereHas('roles', function ($query) {
            $query->whereIn('name', ['superadmin', 'admin_conjunto', 'consejo']);
        });
    }

    /**
     * Check if user is an administrative user.
     */
    public function isAdministrative(): bool
    {
        return $this->hasAnyRole(['superadmin', 'admin_conjunto', 'consejo']);
    }

    /**
     * Get full name with position.
     */
    public function getFullNameWithPositionAttribute(): string
    {
        return $this->position ? "{$this->name} - {$this->position}" : $this->name;
    }

    /**
     * Get all reservations made by this user.
     */
    public function reservations()
    {
        return $this->hasMany(Reservation::class);
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification()
    {
        $this->notify(new \App\Notifications\CustomVerifyEmailNotification);
    }

    /**
     * Get the tenant this user belongs to.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the active subscription for this user (for central admins without tenant).
     */
    public function activeSubscription()
    {
        return $this->hasOne(TenantSubscription::class, 'user_id')
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Get all subscriptions for this user.
     */
    public function subscriptions()
    {
        return $this->hasMany(TenantSubscription::class, 'user_id');
    }

    /**
     * Check if user has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->activeSubscription()->exists();
    }

    /**
     * Check if user is a central admin that needs a subscription.
     */
    public function needsSubscription(): bool
    {
        return $this->hasRole('admin') &&
               ! $this->tenant_id &&
               ! $this->hasActiveSubscription();
    }

    /**
     * Mark user as requiring subscription.
     */
    public function markAsRequiringSubscription(): void
    {
        $this->update([
            'requires_subscription' => true,
            'subscription_required_at' => now(),
        ]);
    }
}
