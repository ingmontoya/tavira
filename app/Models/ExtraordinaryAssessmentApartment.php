<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExtraordinaryAssessmentApartment extends Model
{
    protected $fillable = [
        'extraordinary_assessment_id',
        'apartment_id',
        'total_amount',
        'installment_amount',
        'installments_paid',
        'amount_paid',
        'amount_pending',
        'status',
        'first_payment_date',
        'last_payment_date',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'installment_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'amount_pending' => 'decimal:2',
        'installments_paid' => 'integer',
        'first_payment_date' => 'date',
        'last_payment_date' => 'date',
    ];

    protected $appends = [
        'progress_percentage',
        'status_label',
    ];

    /**
     * Relationships
     */
    public function extraordinaryAssessment(): BelongsTo
    {
        return $this->belongsTo(ExtraordinaryAssessment::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    /**
     * Attributes
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        return round(($this->amount_paid / $this->total_amount) * 100, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'in_progress' => 'En Progreso',
            'completed' => 'Completado',
            'overdue' => 'Vencido',
            default => 'Sin estado',
        };
    }

    /**
     * Methods
     */

    /**
     * Registra un pago aplicado a esta cuota extraordinaria
     */
    public function registerPayment(float $amount): void
    {
        $this->amount_paid += $amount;
        $this->amount_pending = max(0, $this->total_amount - $this->amount_paid);
        $this->installments_paid = (int) floor($this->amount_paid / $this->installment_amount);

        // Actualizar fechas
        if (is_null($this->first_payment_date)) {
            $this->first_payment_date = now();
        }
        $this->last_payment_date = now();

        // Actualizar estado
        $this->updateStatus();
        $this->save();

        // Actualizar el progreso de la cuota extraordinaria principal
        $this->extraordinaryAssessment->updateCollectionProgress();
    }

    /**
     * Actualiza el estado basado en el progreso de pago
     */
    public function updateStatus(): void
    {
        if ($this->amount_pending <= 0) {
            $this->status = 'completed';
        } elseif ($this->amount_paid > 0) {
            $this->status = 'in_progress';
        } else {
            $this->status = 'pending';
        }
    }
}
