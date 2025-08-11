<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'apartment_id',
        'created_by',
        'visitor_name',
        'visitor_document_type',
        'visitor_document_number',
        'visitor_phone',
        'visit_reason',
        'valid_from',
        'valid_until',
        'qr_code',
        'status',
        'entry_time',
        'exit_time',
        'authorized_by',
        'security_notes',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'entry_time' => 'datetime',
        'exit_time' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($visit) {
            if (empty($visit->qr_code)) {
                $visit->qr_code = self::generateUniqueQrCode();
            }
        });
    }

    public static function generateUniqueQrCode(): string
    {
        do {
            $code = strtoupper(Str::random(12));
        } while (self::where('qr_code', $code)->exists());

        return $code;
    }

    public function apartment()
    {
        return $this->belongsTo(Apartment::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function authorizer()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeValid(Builder $query): Builder
    {
        $now = now();

        return $query->where('valid_from', '<=', $now)
            ->where('valid_until', '>=', $now)
            ->whereIn('status', ['pending', 'active']);
    }

    public function scopeByApartment(Builder $query, int $apartmentId): Builder
    {
        return $query->where('apartment_id', $apartmentId);
    }

    public function isValid(): bool
    {
        $now = now();

        return $this->valid_from <= $now
            && $this->valid_until >= $now
            && in_array($this->status, ['pending', 'active']);
    }

    public function canBeUsed(): bool
    {
        return $this->isValid() && $this->status === 'pending';
    }

    public function markAsUsed($authorizedBy = null): void
    {
        $this->update([
            'status' => 'used',
            'entry_time' => now(),
            'authorized_by' => $authorizedBy,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function getFormattedValidityAttribute(): string
    {
        return "Válida desde {$this->valid_from->format('d/m/Y H:i')} hasta {$this->valid_until->format('d/m/Y H:i')}";
    }
}
