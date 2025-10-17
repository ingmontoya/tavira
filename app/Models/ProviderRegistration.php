<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ProviderRegistration extends Model
{
    use CentralConnection;

    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'service_type',
        'description',
        'status',
        'admin_notes',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            \App\Models\Central\ProviderCategory::class,
            'provider_registration_category',
            'provider_registration_id',
            'provider_category_id'
        )->withTimestamps();
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }
}
