<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceStaff extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'name',
        'phone',
        'email',
        'specialties',
        'hourly_rate',
        'is_internal',
        'is_active',
        'availability_schedule',
    ];

    protected $casts = [
        'specialties' => 'array',
        'is_internal' => 'boolean',
        'is_active' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'availability_schedule' => 'array',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function maintenanceRequests(): HasMany
    {
        return $this->hasMany(MaintenanceRequest::class, 'assigned_staff_id');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'assigned_staff_id');
    }
}
