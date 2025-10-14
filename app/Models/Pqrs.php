<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pqrs extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'pqrs';

    protected $fillable = [
        'type',
        'subject',
        'description',
        'user_id',
        'submitter_name',
        'submitter_email',
        'submitter_phone',
        'apartment_id',
        'status',
        'priority',
        'assigned_to',
        'admin_response',
        'responded_at',
        'resolved_at',
        'ticket_number',
        'is_public',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'resolved_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pqrs) {
            if (empty($pqrs->ticket_number)) {
                $pqrs->ticket_number = self::generateTicketNumber();
            }
        });
    }

    /**
     * Generate a unique ticket number
     */
    private static function generateTicketNumber(): string
    {
        do {
            $number = 'PQRS-'.date('Y').'-'.str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
        } while (self::where('ticket_number', $number)->exists());

        return $number;
    }

    /**
     * Get the user who submitted the PQRS (if authenticated)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the apartment related to the PQRS
     */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    /**
     * Get the admin user assigned to handle the PQRS
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Get the submitter name (from user or submitted field)
     */
    public function getSubmitterNameAttribute(): ?string
    {
        return $this->attributes['submitter_name'] ?? $this->user?->name;
    }

    /**
     * Get the submitter email (from user or submitted field)
     */
    public function getSubmitterEmailAttribute(): ?string
    {
        return $this->attributes['submitter_email'] ?? $this->user?->email;
    }

    /**
     * Scope for filtering by status
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for pending PQRS
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pendiente');
    }

    /**
     * Scope for resolved PQRS
     */
    public function scopeResolved($query)
    {
        return $query->where('status', 'resuelta');
    }
}
