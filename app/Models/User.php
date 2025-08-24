<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
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
        'phone',
        'position',
        'department',
        'is_active',
        'avatar',
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
}
