<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReservableAsset extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'name',
        'description',
        'type',
        'availability_rules',
        'max_reservations_per_user',
        'reservation_duration_minutes',
        'advance_booking_days',
        'reservation_cost',
        'requires_approval',
        'is_active',
        'image_path',
        'metadata',
    ];

    protected $casts = [
        'availability_rules' => 'array',
        'reservation_cost' => 'decimal:2',
        'requires_approval' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function activeReservations(): HasMany
    {
        return $this->hasMany(Reservation::class)
            ->whereIn('status', ['approved', 'pending']);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    public function isAvailableAt(Carbon $startTime, Carbon $endTime): bool
    {
        // Check if there are any overlapping reservations
        $conflictingReservations = $this->reservations()
            ->whereIn('status', ['approved', 'pending'])
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime) {
                    // Start time falls within existing reservation
                    $q->where('start_time', '<=', $startTime)
                        ->where('end_time', '>', $startTime);
                })->orWhere(function ($q) use ($endTime) {
                    // End time falls within existing reservation
                    $q->where('start_time', '<', $endTime)
                        ->where('end_time', '>=', $endTime);
                })->orWhere(function ($q) use ($startTime, $endTime) {
                    // New reservation encompasses existing reservation
                    $q->where('start_time', '>=', $startTime)
                        ->where('end_time', '<=', $endTime);
                });
            })
            ->exists();

        if ($conflictingReservations) {
            return false;
        }

        // Check availability rules if they exist
        if (! empty($this->availability_rules)) {
            return $this->checkAvailabilityRules($startTime, $endTime);
        }

        return true;
    }

    private function checkAvailabilityRules(Carbon $startTime, Carbon $endTime): bool
    {
        $rules = $this->availability_rules;

        // Check allowed days of week (0 = Sunday, 6 = Saturday)
        if (isset($rules['allowed_days']) && ! empty($rules['allowed_days'])) {
            $dayOfWeek = $startTime->dayOfWeek;
            if (! in_array($dayOfWeek, $rules['allowed_days'])) {
                return false;
            }
        }

        // Check time slots
        if (isset($rules['time_slots']) && ! empty($rules['time_slots'])) {
            $startHour = $startTime->format('H:i');
            $endHour = $endTime->format('H:i');

            $withinTimeSlot = false;
            foreach ($rules['time_slots'] as $slot) {
                if ($startHour >= $slot['start'] && $endHour <= $slot['end']) {
                    $withinTimeSlot = true;
                    break;
                }
            }

            if (! $withinTimeSlot) {
                return false;
            }
        }

        return true;
    }

    public function canUserReserve(int $userId): bool
    {
        if ($this->max_reservations_per_user <= 0) {
            return true;
        }

        $userReservationsCount = $this->reservations()
            ->where('user_id', $userId)
            ->whereIn('status', ['approved', 'pending'])
            ->where('start_time', '>=', now())
            ->count();

        return $userReservationsCount < $this->max_reservations_per_user;
    }

    public function getMaxAdvanceBookingDate(): Carbon
    {
        return now()->addDays($this->advance_booking_days);
    }
}
