<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conjunto_config_id',
        'name',
        'document_type',
        'document_number',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'contact_name',
        'contact_phone',
        'contact_email',
        'notes',
        'tax_regime',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'full_contact_info',
        'status_badge',
    ];

    // Relationships
    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class);
    }

    // Scopes
    public function scopeForConjunto($query, int $conjuntoConfigId)
    {
        return $query->where('conjunto_config_id', $conjuntoConfigId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDocumentNumber($query, string $documentNumber)
    {
        return $query->where('document_number', $documentNumber);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }

    // Attributes
    public function getFullContactInfoAttribute(): string
    {
        $parts = [];

        if ($this->email) {
            $parts[] = $this->email;
        }

        if ($this->phone) {
            $parts[] = $this->phone;
        }

        if ($this->contact_name) {
            $parts[] = "Contacto: {$this->contact_name}";
        }

        return implode(' | ', $parts);
    }

    public function getStatusBadgeAttribute(): array
    {
        return $this->is_active
            ? ['text' => 'Activo', 'class' => 'bg-green-100 text-green-800']
            : ['text' => 'Inactivo', 'class' => 'bg-gray-100 text-gray-800'];
    }

    // Methods
    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function canBeDeleted(): bool
    {
        return $this->expenses()->count() === 0;
    }
}
