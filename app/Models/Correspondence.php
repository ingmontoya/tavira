<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Correspondence extends Model
{
    use HasFactory;

    protected $fillable = [
        'conjunto_config_id',
        'tracking_number',
        'sender_name',
        'sender_company',
        'type',
        'description',
        'apartment_id',
        'status',
        'received_by',
        'received_at',
        'delivered_by',
        'delivered_at',
        'delivery_notes',
        'requires_signature',
        'signature_path',
        'recipient_name',
        'recipient_document',
    ];

    protected $casts = [
        'received_at' => 'datetime',
        'delivered_at' => 'datetime',
        'requires_signature' => 'boolean',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function deliveredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'delivered_by');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CorrespondenceAttachment::class);
    }

    public function photoEvidence(): HasMany
    {
        return $this->hasMany(CorrespondenceAttachment::class)->where('type', 'photo_evidence');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(CorrespondenceAttachment::class)->where('type', 'signature');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($correspondence) {
            if (empty($correspondence->tracking_number)) {
                $correspondence->tracking_number = 'CORR-'.date('Y').'-'.str_pad(
                    static::whereYear('created_at', now()->year)->count() + 1,
                    6,
                    '0',
                    STR_PAD_LEFT
                );
            }
        });
    }
}
