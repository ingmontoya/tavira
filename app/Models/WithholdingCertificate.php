<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WithholdingCertificate extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'provider_id',
        'year',
        'certificate_number',
        'total_base',
        'total_retained',
        'issued_at',
        'issued_by',
        'pdf_path',
    ];

    protected $casts = [
        'total_base' => 'decimal:2',
        'total_retained' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function details(): HasMany
    {
        return $this->hasMany(WithholdingCertificateDetail::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function scopeForConjunto($query, int $conjuntoConfigId)
    {
        return $query->where('conjunto_config_id', $conjuntoConfigId);
    }

    public function scopeByYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('provider_id', $providerId);
    }
}
