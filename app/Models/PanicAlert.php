<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PanicAlert extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'apartment_id',
        'lat',
        'lng',
        'status',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
    ];

    /**
     * Get the user that triggered the panic alert.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the apartment associated with the panic alert.
     */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    /**
     * Scope to get only active alerts (triggered or confirmed).
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['triggered', 'confirmed']);
    }

    /**
     * Scope to get alerts by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if the alert is active.
     */
    public function isActive(): bool
    {
        return in_array($this->status, ['triggered', 'confirmed']);
    }

    /**
     * Get the status badge for display.
     */
    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'triggered' => ['text' => 'Activada', 'class' => 'bg-red-100 text-red-800'],
            'confirmed' => ['text' => 'Confirmada', 'class' => 'bg-red-200 text-red-900'],
            'resolved' => ['text' => 'Resuelta', 'class' => 'bg-green-100 text-green-800'],
            'cancelled' => ['text' => 'Cancelada', 'class' => 'bg-gray-100 text-gray-800'],
            default => ['text' => 'Desconocido', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    /**
     * Get location string if coordinates are available.
     */
    public function getLocationStringAttribute(): ?string
    {
        if ($this->lat && $this->lng) {
            return "{$this->lat}, {$this->lng}";
        }
        return null;
    }

    /**
     * Get user display name for the alert.
     */
    public function getUserDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }
        return 'Usuario desconocido';
    }

    /**
     * Get apartment display name for the alert.
     */
    public function getApartmentDisplayNameAttribute(): string
    {
        if ($this->apartment) {
            return $this->apartment->full_address;
        }
        return 'Apartamento no especificado';
    }
}