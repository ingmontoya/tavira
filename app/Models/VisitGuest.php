<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitGuest extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'guest_name',
        'document_type',
        'document_number',
        'phone',
        'vehicle_plate',
        'vehicle_color',
    ];

    /**
     * Get the visit that owns this guest.
     */
    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    /**
     * Check if the guest has a vehicle.
     */
    public function hasVehicle(): bool
    {
        return ! empty($this->vehicle_plate);
    }

    /**
     * Get full document info (type + number).
     */
    public function getFullDocumentAttribute(): string
    {
        return "{$this->document_type} {$this->document_number}";
    }
}
