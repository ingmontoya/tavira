<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuotationResponse extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'quotation_request_id',
        'provider_id',
        'quoted_amount',
        'proposal',
        'attachments',
        'estimated_days',
        'status',
        'admin_notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quoted_amount' => 'decimal:2',
        'attachments' => 'array',
        'estimated_days' => 'integer',
    ];

    /**
     * Relationships
     */
    public function quotationRequest(): BelongsTo
    {
        return $this->belongsTo(QuotationRequest::class);
    }

    /**
     * Get the provider from the central database.
     * Note: Providers are stored in the central database, not the tenant database,
     * so this relationship crosses database boundaries.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Central\Provider::class, 'provider_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Methods
     */
    public function accept(): void
    {
        $this->update(['status' => 'accepted']);
    }

    public function reject(string $notes = null): void
    {
        $this->update([
            'status' => 'rejected',
            'admin_notes' => $notes,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
