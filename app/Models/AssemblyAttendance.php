<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssemblyAttendance extends Model
{
    protected $fillable = [
        'assembly_id',
        'user_id',
        'apartment_id',
        'status',
        'registered_at',
        'registered_by',
        'metadata',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(Assembly::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function scopeForAssembly($query, int $assemblyId)
    {
        return $query->where('assembly_id', $assemblyId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePresent($query)
    {
        return $query->where('status', 'present');
    }

    public function scopeAbsent($query)
    {
        return $query->where('status', 'absent');
    }

    public function scopeDelegated($query)
    {
        return $query->where('status', 'delegated');
    }
}
