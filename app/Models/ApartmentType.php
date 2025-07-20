<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApartmentType extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'name',
        'description',
        'area_sqm',
        'bedrooms',
        'bathrooms',
        'has_balcony',
        'has_laundry_room',
        'has_maid_room',
        'coefficient',
        'administration_fee',
        'floor_positions',
        'features',
    ];

    protected $casts = [
        'area_sqm' => 'decimal:2',
        'coefficient' => 'decimal:6',
        'administration_fee' => 'decimal:2',
        'has_balcony' => 'boolean',
        'has_laundry_room' => 'boolean',
        'has_maid_room' => 'boolean',
        'floor_positions' => 'array',
        'features' => 'array',
    ];


    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} - {$this->area_sqm}m²";
    }

    public function getFeaturesSummaryAttribute(): string
    {
        $features = [];
        
        if ($this->bedrooms) {
            $features[] = "{$this->bedrooms} hab";
        }
        
        if ($this->bathrooms) {
            $features[] = "{$this->bathrooms} baños";
        }
        
        if ($this->has_balcony) {
            $features[] = "balcón";
        }
        
        if ($this->has_laundry_room) {
            $features[] = "lavandería";
        }
        
        if ($this->has_maid_room) {
            $features[] = "cuarto servicio";
        }
        
        return implode(', ', $features);
    }
}
