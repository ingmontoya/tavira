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
        'is_active' => 'boolean',
        'tower_names' => 'array',
        'configuration_metadata' => 'array',
    ];

    public function apartmentTypes(): HasMany
    {
        return $this->hasMany(ApartmentType::class);
    }

    public function apartments(): HasMany
    {
        return $this->hasMany(Apartment::class);
    }

    public function paymentConcepts(): HasMany
    {
        return $this->hasMany(PaymentConcept::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function maintenanceCategories(): HasMany
    {
        return $this->hasMany(MaintenanceCategory::class);
    }

    public function maintenanceStaff(): HasMany
    {
        return $this->hasMany(MaintenanceStaff::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    public function getEstimatedApartmentsCountAttribute(): int
    {
        $totalApartments = 0;

        // Calculate apartments per floor considering special configurations
        for ($floor = 1; $floor <= $this->floors_per_tower; $floor++) {
            $totalApartments += $this->getApartmentsCountForFloor($floor);
        }

        // Multiply by number of towers
        return $this->number_of_towers * $totalApartments;
    }

    public function getTowerNamesListAttribute(): array
    {
        if ($this->tower_names && is_array($this->tower_names)) {
            return $this->tower_names;
        }

        // Generate default tower names (1, 2, 3, ...)
        return range(1, $this->number_of_towers);
    }

    public function canGenerateApartments(): bool
    {
        return $this->apartmentTypes()->count() > 0;
    }

    public function generateApartments(): void
    {
        if (! $this->canGenerateApartments()) {
            throw new \Exception('No se pueden generar apartamentos sin tipos de apartamento definidos.');
        }

        $apartmentTypes = $this->apartmentTypes;
        $towerNames = $this->tower_names_list;

        for ($towerIndex = 0; $towerIndex < $this->number_of_towers; $towerIndex++) {
            $towerName = $towerNames[$towerIndex] ?? ($towerIndex + 1);

            for ($floor = 1; $floor <= $this->floors_per_tower; $floor++) {
                // Get the actual apartments count for this floor based on apartment types
                $actualApartmentsThisFloor = $this->getApartmentsCountForFloor($floor);

                for ($position = 1; $position <= $actualApartmentsThisFloor; $position++) {
                    $apartmentNumber = $towerName.$floor.sprintf('%02d', $position);

                    // Select apartment type based on floor_positions and floor-specific logic
                    $apartmentType = $this->selectApartmentTypeForPosition($apartmentTypes, $floor, $position);

                    // Check if apartment already exists
                    $existingApartment = $this->apartments()
                        ->where('number', $apartmentNumber)
                        ->where('tower', $towerName)
                        ->first();

                    if (! $existingApartment) {
                        $this->apartments()->create([
                            'apartment_type_id' => $apartmentType->id,
                            'number' => $apartmentNumber,
                            'tower' => (string) $towerName,
                            'floor' => $floor,
                            'position_on_floor' => $position,
                            'status' => 'Available',
                            'monthly_fee' => $apartmentType->administration_fee,
                            'utilities' => [],
                            'features' => [],
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Calculate the number of apartments for a specific floor
     * This allows for different configurations per floor
     */
    private function getApartmentsCountForFloor(int $floor): int
    {
        // Check if we have floor-specific configuration
        $floorConfig = $this->configuration_metadata['floor_configuration'] ?? null;

        if ($floorConfig && isset($floorConfig[$floor])) {
            return $floorConfig[$floor]['apartments_count'] ?? $this->apartments_per_floor;
        }

        // Check for special floors (like penthouses on top floor)
        if ($floor == $this->floors_per_tower) {
            $penthouseConfig = $this->configuration_metadata['penthouse_configuration'] ?? null;
            if ($penthouseConfig && isset($penthouseConfig['apartments_count'])) {
                return $penthouseConfig['apartments_count'];
            }
        }

        // Default to the standard apartments per floor
        return $this->apartments_per_floor;
    }

    /**
     * Select the appropriate apartment type for a given floor and position
     */
    private function selectApartmentTypeForPosition($apartmentTypes, int $floor, int $position)
    {
        // First, try floor-specific type assignment
        $floorConfig = $this->configuration_metadata['floor_configuration'] ?? null;
        if ($floorConfig && isset($floorConfig[$floor]['type_assignments'][$position])) {
            $typeId = $floorConfig[$floor]['type_assignments'][$position];
            $specificType = $apartmentTypes->firstWhere('id', $typeId);
            if ($specificType) {
                return $specificType;
            }
        }

        // Check for penthouse on top floor
        if ($floor == $this->floors_per_tower) {
            $penthouseType = $apartmentTypes->where('name', 'like', '%penthouse%')->first() ??
                           $apartmentTypes->where('name', 'like', '%Penthouse%')->first();
            if ($penthouseType) {
                return $penthouseType;
            }
        }

        // Standard logic: Select apartment type based on floor_positions
        $apartmentType = $apartmentTypes->first(function ($type) use ($position) {
            $positions = $type->floor_positions ?? [];

            return in_array($position, $positions);
        });

        // Fallback to first type if no type matches the position
        if (! $apartmentType) {
            $apartmentType = $apartmentTypes->first();
        }

        return $apartmentType;
    }
}
