<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'priority',
        'type',
        'status',
        'is_pinned',
        'requires_confirmation',
        'attachments',
        'published_at',
        'expires_at',
        'created_by',
        'updated_by',
        'target_scope',
        'target_towers',
        'target_apartment_type_ids',
        'target_apartment_ids',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_pinned' => 'boolean',
        'requires_confirmation' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'target_towers' => 'array',
        'target_apartment_type_ids' => 'array',
        'target_apartment_ids' => 'array',
    ];

    // Scopes
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    public function scopePinned(Builder $query): Builder
    {
        return $query->where('is_pinned', true);
    }

    public function scopeByPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->active()
            ->orderByDesc('is_pinned')
            ->orderBy(\DB::raw("CASE 
                WHEN priority = 'urgent' THEN 1 
                WHEN priority = 'important' THEN 2 
                WHEN priority = 'normal' THEN 3 
                ELSE 4 
            END"))
            ->orderByDesc('published_at');
    }

    public function scopeForResident(Builder $query, int $apartmentId): Builder
    {
        $apartment = \App\Models\Apartment::with(['apartmentType'])
            ->findOrFail($apartmentId);

        return $query->active()
            ->where(function ($q) use ($apartment) {
                // General announcements
                $q->where('target_scope', 'general')
                  // Tower-specific announcements
                    ->orWhere(function ($sq) use ($apartment) {
                        $sq->where('target_scope', 'tower')
                            ->whereJsonContains('target_towers', $apartment->tower);
                    })
                  // Apartment type-specific announcements
                    ->orWhere(function ($sq) use ($apartment) {
                        $sq->where('target_scope', 'apartment_type')
                            ->whereJsonContains('target_apartment_type_ids', $apartment->apartment_type_id);
                    })
                  // Individual apartment announcements
                    ->orWhere(function ($sq) use ($apartment) {
                        $sq->where('target_scope', 'apartment')
                            ->whereJsonContains('target_apartment_ids', $apartment->id);
                    });
            })
            ->orderByDesc('is_pinned')
            ->orderBy(\DB::raw("CASE 
                WHEN priority = 'urgent' THEN 1 
                WHEN priority = 'important' THEN 2 
                WHEN priority = 'normal' THEN 3 
                ELSE 4 
            END"))
            ->orderByDesc('published_at');
    }

    public function scopeByTargetScope(Builder $query, string $scope): Builder
    {
        return $query->where('target_scope', $scope);
    }

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function confirmations(): HasMany
    {
        return $this->hasMany(AnnouncementConfirmation::class);
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'published' && ! $this->is_expired;
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'destructive',
            'important' => 'warning',
            default => 'secondary',
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match ($this->type) {
            'emergency' => 'destructive',
            'maintenance' => 'warning',
            'administrative' => 'primary',
            default => 'secondary',
        };
    }

    // Helper methods
    public function isConfirmedBy(int $userId): bool
    {
        return $this->confirmations()
            ->where('user_id', $userId)
            ->whereNotNull('confirmed_at')
            ->exists();
    }

    public function getTargetScopeDisplayAttribute(): string
    {
        return match ($this->target_scope) {
            'general' => 'General (Todo el conjunto)',
            'tower' => 'Por torre(s)',
            'apartment_type' => 'Por tipo de apartamento',
            'apartment' => 'Apartamento específico',
            default => 'General',
        };
    }

    public function getTargetDetailsAttribute(): string
    {
        return match ($this->target_scope) {
            'tower' => $this->target_towers ? 'Torres: '.implode(', ', $this->target_towers) : '',
            'apartment_type' => $this->getApartmentTypesNames(),
            'apartment' => $this->getApartmentNumbers(),
            default => '',
        };
    }

    public function getApartmentTypesNames(): string
    {
        if (! $this->target_apartment_type_ids) {
            return '';
        }

        $types = \App\Models\ApartmentType::whereIn('id', $this->target_apartment_type_ids)
            ->pluck('name')
            ->toArray();

        return 'Tipos: '.implode(', ', $types);
    }

    public function getApartmentNumbers(): string
    {
        if (! $this->target_apartment_ids) {
            return '';
        }

        $apartments = \App\Models\Apartment::whereIn('id', $this->target_apartment_ids)
            ->get()
            ->map(function ($apt) {
                return $apt->full_address;
            })
            ->toArray();

        return 'Apartamentos: '.implode(', ', $apartments);
    }

    public function isVisibleToApartment(int $apartmentId): bool
    {
        if ($this->target_scope === 'general') {
            return true;
        }

        $apartment = \App\Models\Apartment::with(['apartmentType'])
            ->find($apartmentId);

        if (! $apartment) {
            return false;
        }

        return match ($this->target_scope) {
            'tower' => in_array($apartment->tower, $this->target_towers ?? []),
            'apartment_type' => in_array($apartment->apartment_type_id, $this->target_apartment_type_ids ?? []),
            'apartment' => in_array($apartment->id, $this->target_apartment_ids ?? []),
            default => false,
        };
    }

    public function isReadBy(int $userId): bool
    {
        return $this->confirmations()
            ->where('user_id', $userId)
            ->whereNotNull('read_at')
            ->exists();
    }

    public function markAsReadBy(int $userId): void
    {
        $this->confirmations()->updateOrCreate(
            ['user_id' => $userId],
            ['read_at' => now()]
        );
    }

    public function markAsConfirmedBy(int $userId): void
    {
        $this->confirmations()->updateOrCreate(
            ['user_id' => $userId],
            [
                'read_at' => now(),
                'confirmed_at' => now(),
            ]
        );
    }
}
