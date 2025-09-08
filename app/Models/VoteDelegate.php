<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoteDelegate extends Model
{
    protected $fillable = [
        'assembly_id',
        'delegator_apartment_id',
        'delegate_user_id',
        'authorized_by_user_id',
        'status',
        'authorized_at',
        'expires_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'authorized_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'is_active',
        'is_expired',
        'status_badge',
    ];

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(Assembly::class);
    }

    public function delegatorApartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class, 'delegator_apartment_id');
    }

    public function delegateUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delegate_user_id');
    }

    public function authorizedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' && 
               (!$this->expires_at || now()->lt($this->expires_at));
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && now()->gte($this->expires_at);
    }

    public function getStatusBadgeAttribute(): array
    {
        if ($this->is_expired) {
            return ['text' => 'Expirado', 'class' => 'bg-gray-100 text-gray-800'];
        }

        return match ($this->status) {
            'pending' => ['text' => 'Pendiente', 'class' => 'bg-yellow-100 text-yellow-800'],
            'active' => ['text' => 'Activo', 'class' => 'bg-green-100 text-green-800'],
            'revoked' => ['text' => 'Revocado', 'class' => 'bg-red-100 text-red-800'],
            'expired' => ['text' => 'Expirado', 'class' => 'bg-gray-100 text-gray-800'],
            default => ['text' => 'Unknown', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->where(function ($query) {
                        $query->whereNull('expires_at')
                              ->orWhere('expires_at', '>', now());
                    });
    }

    public function scopeForAssembly($query, int $assemblyId)
    {
        return $query->where('assembly_id', $assemblyId);
    }

    public function scopeForApartment($query, int $apartmentId)
    {
        return $query->where('delegator_apartment_id', $apartmentId);
    }

    public function scopeForDelegate($query, int $userId)
    {
        return $query->where('delegate_user_id', $userId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function approve(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        $this->update([
            'status' => 'active',
            'authorized_at' => now(),
        ]);

        return true;
    }

    public function revoke(): bool
    {
        if (!in_array($this->status, ['pending', 'active'])) {
            return false;
        }

        $this->update(['status' => 'revoked']);

        return true;
    }

    public function canVoteFor(int $apartmentId): bool
    {
        return $this->is_active && 
               $this->delegator_apartment_id === $apartmentId;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($delegate) {
            if (!$delegate->expires_at && $delegate->assembly) {
                // Set expiration to assembly end date by default
                $delegate->expires_at = $delegate->assembly->scheduled_at->addDays(1);
            }
        });
    }
}
