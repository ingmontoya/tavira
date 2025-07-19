<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConjuntoConfig extends Model
{
    protected $fillable = [
        'name',
        'description',
        'number_of_towers',
        'floors_per_tower',
        'apartments_per_floor',
        'is_active',
        'tower_names',
        'configuration_metadata',
    ];

    protected $casts = [
        'tower_names' => 'array',
        'configuration_metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function apartmentTypes(): HasMany
    {
        return $this->hasMany(ApartmentType::class);
    }

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class);
    }

    public function getTotalApartmentsAttribute(): int
    {
        return $this->number_of_towers * $this->floors_per_tower * $this->apartments_per_floor;
    }

    public function generateApartments(): void
    {
        // Clear existing apartments for this conjunto
        $this->apartments()->delete();
        
        // Use numeric tower names (1, 2, 3, etc.) or custom names
        $towerNames = $this->tower_names ?? range(1, $this->number_of_towers);
        
        foreach ($towerNames as $towerIndex => $tower) {
            // Use tower number for apartment numbering (1-based)
            $towerNumber = is_numeric($tower) ? $tower : ($towerIndex + 1);
            
            for ($floor = 1; $floor <= $this->floors_per_tower; $floor++) {
                for ($position = 1; $position <= $this->apartments_per_floor; $position++) {
                    // Generate apartment number: [tower][floor][position]
                    // Example: Tower 4, Floor 1, Position 1 = 4101
                    $apartmentNumber = $towerNumber . str_pad($floor, 1, '0', STR_PAD_LEFT) . str_pad($position, 2, '0', STR_PAD_LEFT);
                    
                    // Get apartment type based on position (cycling through available types)
                    $apartmentTypes = $this->apartmentTypes;
                    if ($apartmentTypes->count() > 0) {
                        $apartmentType = $apartmentTypes[($position - 1) % $apartmentTypes->count()];
                        
                        Apartment::create([
                            'conjunto_config_id' => $this->id,
                            'apartment_type_id' => $apartmentType->id,
                            'number' => $apartmentNumber,
                            'tower' => (string) $tower,
                            'floor' => $floor,
                            'position_on_floor' => $position,
                            'monthly_fee' => $apartmentType->administration_fee,
                            'status' => 'Available',
                        ]);
                    }
                }
            }
        }
    }
    
    public function canGenerateApartments(): bool
    {
        return $this->apartmentTypes()->count() > 0 && 
               $this->number_of_towers > 0 && 
               $this->floors_per_tower > 0 && 
               $this->apartments_per_floor > 0;
    }
    
    public function getEstimatedApartmentsCountAttribute(): int
    {
        return $this->number_of_towers * $this->floors_per_tower * $this->apartments_per_floor;
    }
}
