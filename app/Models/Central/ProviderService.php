<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ProviderService extends Model
{
    use CentralConnection, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'provider_id',
        'name',
        'description',
        'price',
        'price_type',
        'unit',
        'category_id',
        'is_active',
        'images',
        'specifications',
        'terms',
        'estimated_delivery_days',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
        'images' => 'array',
        'specifications' => 'array',
        'estimated_delivery_days' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'formatted_price',
        'status_badge',
    ];

    /**
     * Relationships
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProviderCategory::class, 'category_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    public function scopeByCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByPriceType($query, string $priceType)
    {
        return $query->where('price_type', $priceType);
    }

    /**
     * Accessors
     */
    public function getFormattedPriceAttribute(): string
    {
        if (! $this->price) {
            return 'A cotizar';
        }

        $formattedPrice = '$'.number_format($this->price, 0, ',', '.');

        if ($this->unit) {
            return $formattedPrice.' / '.$this->unit;
        }

        return $formattedPrice;
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

    public function hasImages(): bool
    {
        return ! empty($this->images);
    }

    public function getFirstImage(): ?string
    {
        return $this->images[0] ?? null;
    }
}
