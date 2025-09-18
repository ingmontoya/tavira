<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    protected $fillable = [
        'reservable_asset_id',
        'user_id',
        'apartment_id',
        'start_time',
        'end_time',
        'status',
        'notes',
        'admin_notes',
        'cost',
        'payment_required',
        'payment_status',
        'approved_at',
        'approved_by',
        'cancelled_at',
        'cancelled_by',
        'metadata',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'approved_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'cost' => 'decimal:2',
        'payment_required' => 'boolean',
        'metadata' => 'array',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const STATUS_CANCELLED = 'cancelled';

    const STATUS_COMPLETED = 'completed';

    const PAYMENT_STATUS_PENDING = 'pending';

    const PAYMENT_STATUS_PAID = 'paid';

    const PAYMENT_STATUS_REFUNDED = 'refunded';

    public function reservableAsset(): BelongsTo
    {
        return $this->belongsTo(ReservableAsset::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopePending(Builder $query): void
    {
        $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeActive(Builder $query): void
    {
        $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_APPROVED]);
    }

    public function scopeUpcoming(Builder $query): void
    {
        $query->where('start_time', '>', now());
    }

    public function scopeToday(Builder $query): void
    {
        $query->whereDate('start_time', today());
    }

    public function scopeForUser(Builder $query, int $userId): void
    {
        $query->where('user_id', $userId);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_APPROVED])
            && $this->start_time > now();
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function approve(?int $approvedById = null): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $approvedById,
        ]);
    }

    public function reject(?int $rejectedById = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
        ]);
    }

    public function cancel(?int $cancelledById = null): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancelled_by' => $cancelledById,
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
        ]);
    }

    public function getDurationInMinutes(): int
    {
        return $this->start_time->diffInMinutes($this->end_time);
    }

    public function getDurationFormatted(): string
    {
        $minutes = $this->getDurationInMinutes();
        $hours = intval($minutes / 60);
        $remainingMinutes = $minutes % 60;

        if ($hours > 0 && $remainingMinutes > 0) {
            return "{$hours}h {$remainingMinutes}min";
        } elseif ($hours > 0) {
            return "{$hours}h";
        } else {
            return "{$remainingMinutes}min";
        }
    }

    public function isInPast(): bool
    {
        return $this->end_time < now();
    }

    public function isInProgress(): bool
    {
        $now = now();

        return $this->start_time <= $now && $this->end_time >= $now;
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_APPROVED => 'green',
            self::STATUS_REJECTED => 'red',
            self::STATUS_CANCELLED => 'gray',
            self::STATUS_COMPLETED => 'blue',
            default => 'gray',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_APPROVED => 'Aprobada',
            self::STATUS_REJECTED => 'Rechazada',
            self::STATUS_CANCELLED => 'Cancelada',
            self::STATUS_COMPLETED => 'Completada',
            default => 'Desconocido',
        };
    }
}
