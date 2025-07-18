<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Apartment extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'apartment_type_id',
        'number',
        'tower',
        'floor',
        'position_on_floor',
        'status',
        'monthly_fee',
        'utilities',
        'features',
        'notes',
    ];

    protected $casts = [
        'monthly_fee' => 'decimal:2',
        'utilities' => 'array',
        'features' => 'array',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function apartmentType(): BelongsTo
    {
        return $this->belongsTo(ApartmentType::class);
    }

    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }

    public function getFullAddressAttribute(): string
    {
        return $this->tower ? "Torre {$this->tower} - Apt {$this->number}" : "Apt {$this->number}";
    }

    public function getIsOccupiedAttribute(): bool
    {
        return $this->status === 'Occupied';
    }

    public function getIsAvailableAttribute(): bool
    {
        return $this->status === 'Available';
    }

    public function scopeByTower($query, string $tower)
    {
        return $query->where('tower', $tower);
    }

    public function scopeByFloor($query, int $floor)
    {
        return $query->where('floor', $floor);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, int $apartmentTypeId)
    {
        return $query->where('apartment_type_id', $apartmentTypeId);
    }
}
