<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Invitation extends Model
{
    protected $fillable = [
        'email',
        'token',
        'role',
        'apartment_id',
        'invited_by',
        'expires_at',
        'accepted_at',
        'accepted_by',
        'message',
        'is_mass_invitation',
        'mass_invitation_title',
        'mass_invitation_description',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'is_mass_invitation' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Invitation $invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(32);
            }
            if (empty($invitation->expires_at)) {
                $invitation->expires_at = now()->addDays(7);
            }
        });
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isAccepted(): bool
    {
        return ! is_null($this->accepted_at);
    }

    public function isPending(): bool
    {
        return ! $this->isExpired() && ! $this->isAccepted();
    }
}
