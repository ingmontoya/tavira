<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\CentralConnection;
use Stancl\Tenancy\Database\Concerns\SyncMaster;

class Provider extends Model
{
    use CentralConnection, SoftDeletes, SyncMaster;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
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
     * Get the tenant model class name for syncing.
     *
     * @return string
     */
    public function getTenantModelName(): string
    {
        return \App\Models\Provider::class;
    }

    /**
     * Get the attributes that should be synced to tenant databases.
     *
     * @return array<int, string>
     */
    public function getSyncedAttributeNames(): array
    {
        return [
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
        ];
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByName($query, string $name)
    {
        return $query->where('name', 'like', "%{$name}%");
    }
}
