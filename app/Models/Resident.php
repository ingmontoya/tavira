<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Resident extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_type',
        'document_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile_phone',
        'birth_date',
        'gender',
        'emergency_contact',
        'apartment_id',
        'resident_type',
        'status',
        'start_date',
        'end_date',
        'notes',
        'documents',
        'email_notifications',
        'whatsapp_notifications',
        'whatsapp_number',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'documents' => 'array',
        'email_notifications' => 'boolean',
        'whatsapp_notifications' => 'boolean',
    ];

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getApartmentFullAttribute(): string
    {
        if (! $this->apartment) {
            return 'Sin apartamento';
        }

        return "{$this->apartment->tower}-{$this->apartment->number}";
    }

    public function getIsOwnerAttribute(): bool
    {
        return $this->resident_type === 'Owner';
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'Active');
    }

    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('status', 'Inactive');
    }

    public function scopeOwners(Builder $query): Builder
    {
        return $query->where('resident_type', 'Owner');
    }

    public function scopeTenants(Builder $query): Builder
    {
        return $query->where('resident_type', 'Tenant');
    }

    public function scopeByApartment(Builder $query, int $apartmentId): Builder
    {
        return $query->where('apartment_id', $apartmentId);
    }

    public function scopeByTower(Builder $query, string $tower): Builder
    {
        return $query->whereHas('apartment', function ($q) use ($tower) {
            $q->where('tower', $tower);
        });
    }

    public function scopeByConjunto(Builder $query, int $conjuntoId): Builder
    {
        return $query->whereHas('apartment', function ($q) use ($conjuntoId) {
            $q->where('conjunto_config_id', $conjuntoId);
        });
    }

    /**
     * Get the apartment that the resident lives in.
     */
    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }
}
