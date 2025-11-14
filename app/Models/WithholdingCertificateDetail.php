<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithholdingCertificateDetail extends Model
{
    protected $fillable = [
        'withholding_certificate_id',
        'expense_id',
        'retention_concept',
        'base_amount',
        'retention_percentage',
        'retained_amount',
        'retention_account_code',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'retention_percentage' => 'decimal:2',
        'retained_amount' => 'decimal:2',
    ];

    public function withholdingCertificate(): BelongsTo
    {
        return $this->belongsTo(WithholdingCertificate::class);
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}
