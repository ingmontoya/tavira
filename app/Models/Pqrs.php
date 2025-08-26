<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pqrs extends Model
{
    use HasFactory;

    protected $table = 'pqrs';

    protected $fillable = [
        'conjunto_config_id',
        'ticket_number',
        'type',
        'subject',
        'description',
        'priority',
        'status',
        'apartment_id',
        'submitted_by',
        'contact_name',
        'contact_email',
        'contact_phone',
        'assigned_to',
        'assigned_at',
        'admin_notes',
        'resolution',
        'resolved_at',
        'resolved_by',
        'requires_follow_up',
        'follow_up_date',
        'follow_up_notes',
        'satisfaction_rating',
        'satisfaction_comments',
        'satisfaction_submitted_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'resolved_at' => 'datetime',
        'follow_up_date' => 'datetime',
        'satisfaction_submitted_at' => 'datetime',
        'requires_follow_up' => 'boolean',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(PqrsAttachment::class);
    }

    public function evidenceAttachments(): HasMany
    {
        return $this->hasMany(PqrsAttachment::class)->where('type', 'evidence');
    }

    public function documentAttachments(): HasMany
    {
        return $this->hasMany(PqrsAttachment::class)->where('type', 'document');
    }

    public function photoAttachments(): HasMany
    {
        return $this->hasMany(PqrsAttachment::class)->where('type', 'photo');
    }

    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'peticion' => 'Petición',
            'queja' => 'Queja',
            'reclamo' => 'Reclamo',
            'sugerencia' => 'Sugerencia',
            default => ucfirst($this->type),
        };
    }

    public function getPriorityDisplayAttribute(): string
    {
        return match ($this->priority) {
            'baja' => 'Baja',
            'media' => 'Media',
            'alta' => 'Alta',
            'urgente' => 'Urgente',
            default => ucfirst($this->priority),
        };
    }

    public function getStatusDisplayAttribute(): string
    {
        return match ($this->status) {
            'abierto' => 'Abierto',
            'en_proceso' => 'En Proceso',
            'resuelto' => 'Resuelto',
            'cerrado' => 'Cerrado',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'abierto' => 'bg-blue-100 text-blue-800',
            'en_proceso' => 'bg-yellow-100 text-yellow-800',
            'resuelto' => 'bg-green-100 text-green-800',
            'cerrado' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'baja' => 'bg-blue-100 text-blue-800',
            'media' => 'bg-yellow-100 text-yellow-800',
            'alta' => 'bg-orange-100 text-orange-800',
            'urgente' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['abierto', 'en_proceso']);
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['resuelto', 'cerrado']);
    }

    public function canBeRated(): bool
    {
        return $this->status === 'resuelto' && is_null($this->satisfaction_rating);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($pqrs) {
            if (empty($pqrs->ticket_number)) {
                $pqrs->ticket_number = 'PQRS-'.date('Y').'-'.str_pad(
                    static::whereYear('created_at', now()->year)->count() + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
