<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentConcept extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'name',
        'description',
        'type',
        'default_amount',
        'is_recurring',
        'is_active',
        'billing_cycle',
        'applicable_apartment_types',
    ];

    protected $casts = [
        'default_amount' => 'decimal:2',
        'is_recurring' => 'boolean',
        'is_active' => 'boolean',
        'applicable_apartment_types' => 'array',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function invoiceItems(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecurring($query)
    {
        return $query->where('is_recurring', true);
    }

    public function isApplicableToApartmentType(int $apartmentTypeId): bool
    {
        if (empty($this->applicable_apartment_types)) {
            return true; // Apply to all types if none specified
        }

        return in_array($apartmentTypeId, $this->applicable_apartment_types);
    }

    /**
     * Sync payment concepts with current apartment types for the conjunto
     */
    public static function syncWithApartmentTypes(int $conjuntoConfigId): void
    {
        $apartmentTypes = \App\Models\ApartmentType::where('conjunto_config_id', $conjuntoConfigId)->get();
        
        if ($apartmentTypes->isEmpty()) {
            return;
        }

        $concepts = self::where('conjunto_config_id', $conjuntoConfigId)->get();
        
        foreach ($concepts as $concept) {
            $matchedTypeIds = [];
            
            // Check if concept name contains references to any apartment type
            foreach ($apartmentTypes as $apartmentType) {
                if (str_contains($concept->name, $apartmentType->name)) {
                    $matchedTypeIds[] = $apartmentType->id;
                }
            }
            
            // If we found specific apartment type references, update the concept
            if (!empty($matchedTypeIds)) {
                $concept->applicable_apartment_types = $matchedTypeIds;
                $concept->save();
            }
            // For concepts without specific type references, keep them as null (apply to all)
        }
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'common_expense' => 'Administración',
            'sanction' => 'Sanción',
            'parking' => 'Parqueadero',
            'special' => 'Especial',
            'late_fee' => 'Interés de mora',
            'other' => 'Otro',
            default => 'Sin clasificar',
        };
    }

    public function getBillingCycleLabelAttribute(): string
    {
        return match ($this->billing_cycle) {
            'monthly' => 'Mensual',
            'quarterly' => 'Trimestral',
            'annually' => 'Anual',
            'one_time' => 'Una vez',
            default => 'Sin definir',
        };
    }
}
