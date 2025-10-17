<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuotationRequest extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'description',
        'deadline',
        'status',
        'attachments',
        'requirements',
        'created_by',
        'published_at',
        'closed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'deadline' => 'date',
        'attachments' => 'array',
        'published_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Relationships
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProviderCategory::class, 'quotation_request_category')
            ->withTimestamps();
    }

    public function responses(): HasMany
    {
        return $this->hasMany(QuotationResponse::class);
    }

    /**
     * Scopes
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeClosed($query)
    {
        return $query->where('status', 'closed');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['draft', 'published']);
    }

    /**
     * Methods
     */
    public function publish(): void
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function close(): void
    {
        $this->update([
            'status' => 'closed',
            'closed_at' => now(),
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function isOpen(): bool
    {
        return $this->status === 'published';
    }

    public function isClosed(): bool
    {
        return in_array($this->status, ['closed', 'cancelled']);
    }

    public function canReceiveResponses(): bool
    {
        return $this->status === 'published' &&
               ($this->deadline === null || $this->deadline->isFuture());
    }
}
