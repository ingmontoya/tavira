<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\ResourceSyncing;

class Provider extends Model
{
    use SoftDeletes, ResourceSyncing;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'global_provider_id', // ID from central database
        'name',
        'category',
        'phone',
        'email',
        'address',
        'document_type',
        'document_number',
        'city',
        'country',
        'contact_name',
        'contact_phone',
        'contact_email',
        'notes',
        'tax_regime',
        'is_active',
        'created_by', // Tenant-specific field
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'full_contact_info',
        'status_badge',
    ];

    /**
     * Get the central model class name for syncing.
     *
     * @return string
     */
    public function getCentralModelName(): string
    {
        return \App\Models\Central\Provider::class;
    }

    /**
     * Get the foreign key name for the central model.
     *
     * @return string
     */
    public function getSyncedAttributeName(): string
    {
        return 'global_provider_id';
    }

    /**
     * Relationships
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'provider_id');
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'provider_id');
    }

    /**
     * Scopes
     */
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

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Accessors
     */
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

    /**
     * Methods
     */
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
        return $this->expenses()->count() === 0 && $this->maintenanceRequests()->count() === 0;
    }

    /**
     * Determine if the provider is synced from central.
     *
     * @return bool
     */
    public function isSynced(): bool
    {
        return ! is_null($this->global_provider_id);
    }
}
