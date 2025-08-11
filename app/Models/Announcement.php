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
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_pinned' => 'boolean',
        'requires_confirmation' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
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
