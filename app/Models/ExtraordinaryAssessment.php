<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExtraordinaryAssessment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'conjunto_config_id',
        'name',
        'description',
        'total_amount',
        'number_of_installments',
        'start_date',
        'end_date',
        'distribution_type',
        'status',
        'total_collected',
        'total_pending',
        'installments_generated',
        'metadata',
        'notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'total_amount' => 'decimal:2',
        'total_collected' => 'decimal:2',
        'total_pending' => 'decimal:2',
        'number_of_installments' => 'integer',
        'installments_generated' => 'integer',
        'metadata' => 'array',
    ];

    protected $appends = [
        'progress_percentage',
        'status_label',
        'distribution_label',
        'is_active_for_current_month',
    ];

    /**
     * Relationships
     */
    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function apartments(): HasMany
    {
        return $this->hasMany(ExtraordinaryAssessmentApartment::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeForCurrentMonth($query, ?Carbon $date = null)
    {
        $date = $date ?? now();

        return $query->where('status', 'active')
            ->where('start_date', '<=', $date->endOfMonth())
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date->startOfMonth());
            });
    }

    /**
     * Attributes
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_amount <= 0) {
            return 0;
        }

        return round(($this->total_collected / $this->total_amount) * 100, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Borrador',
            'active' => 'Activa',
            'completed' => 'Completada',
            'cancelled' => 'Cancelada',
            default => 'Sin estado',
        };
    }

    public function getDistributionLabelAttribute(): string
    {
        return match ($this->distribution_type) {
            'equal' => 'Igual para todos',
            'by_coefficient' => 'Por coeficiente',
            default => 'No definido',
        };
    }

    public function getIsActiveForCurrentMonthAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();

        return $this->start_date->lte($now->endOfMonth())
            && ($this->end_date === null || $this->end_date->gte($now->startOfMonth()));
    }

    /**
     * Methods
     */

    /**
     * Calcula y asigna los montos a cada apartamento
     */
    public function calculateAndAssignApartments(): void
    {
        $apartments = Apartment::where('conjunto_config_id', $this->conjunto_config_id)
            ->whereIn('status', ['Occupied', 'Available'])
            ->with('apartmentType')
            ->get();

        if ($apartments->isEmpty()) {
            throw new \Exception('No hay apartamentos elegibles para asignar la cuota extraordinaria.');
        }

        // Calcular el monto por apartamento según el tipo de distribución
        $apartmentAmounts = $this->calculateApartmentAmounts($apartments);

        // Crear registros de asignación
        foreach ($apartmentAmounts as $apartmentId => $amount) {
            ExtraordinaryAssessmentApartment::updateOrCreate(
                [
                    'extraordinary_assessment_id' => $this->id,
                    'apartment_id' => $apartmentId,
                ],
                [
                    'total_amount' => $amount,
                    'installment_amount' => round($amount / $this->number_of_installments, 2),
                    'amount_pending' => $amount,
                    'status' => 'pending',
                ]
            );
        }

        // Actualizar total_pending
        $this->total_pending = array_sum($apartmentAmounts);
        $this->save();
    }

    /**
     * Calcula el monto que le corresponde a cada apartamento
     */
    private function calculateApartmentAmounts($apartments): array
    {
        $amounts = [];

        if ($this->distribution_type === 'equal') {
            // Distribución igual: dividir el total entre el número de apartamentos
            $amountPerApartment = round($this->total_amount / $apartments->count(), 2);

            foreach ($apartments as $apartment) {
                $amounts[$apartment->id] = $amountPerApartment;
            }
        } else {
            // Distribución por coeficiente de copropiedad
            $totalCoefficient = $apartments->sum(function ($apartment) {
                return $apartment->apartmentType->coefficient;
            });

            foreach ($apartments as $apartment) {
                $coefficient = $apartment->apartmentType->coefficient;
                $amounts[$apartment->id] = round(
                    ($coefficient / $totalCoefficient) * $this->total_amount,
                    2
                );
            }
        }

        return $amounts;
    }

    /**
     * Activa la cuota extraordinaria
     */
    public function activate(): void
    {
        if ($this->status === 'active') {
            throw new \Exception('La cuota extraordinaria ya está activa.');
        }

        // Calcular fecha de finalización
        $this->end_date = $this->start_date->copy()->addMonths($this->number_of_installments - 1)->endOfMonth();
        $this->status = 'active';
        $this->save();

        // Asignar apartamentos si no se ha hecho
        if ($this->apartments()->count() === 0) {
            $this->calculateAndAssignApartments();
        }
    }

    /**
     * Verifica si debe generar una cuota para el mes dado
     */
    public function shouldGenerateInstallmentForMonth(Carbon $date): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        // Verificar que esté dentro del rango de fechas
        if ($date->lt($this->start_date->startOfMonth()) ||
            ($this->end_date && $date->gt($this->end_date->endOfMonth()))) {
            return false;
        }

        // Calcular cuántas cuotas deberían haberse generado hasta esta fecha
        $monthsSinceStart = $this->start_date->diffInMonths($date->startOfMonth()) + 1;
        $expectedInstallments = min($monthsSinceStart, $this->number_of_installments);

        // Si ya se generaron todas las cuotas esperadas, no generar más
        return $this->installments_generated < $expectedInstallments;
    }

    /**
     * Marca una cuota como generada
     */
    public function markInstallmentGenerated(): void
    {
        $this->installments_generated += 1;

        // Si ya se generaron todas las cuotas, marcar como completada
        if ($this->installments_generated >= $this->number_of_installments) {
            $this->status = 'completed';
        }

        $this->save();
    }

    /**
     * Actualiza el progreso de recaudación
     */
    public function updateCollectionProgress(): void
    {
        $collected = $this->apartments()->sum('amount_paid');
        $pending = $this->apartments()->sum('amount_pending');

        $this->total_collected = $collected;
        $this->total_pending = $pending;
        $this->save();
    }
}
