<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MaintenanceRequest extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'maintenance_category_id',
        'apartment_id',
        'requested_by_user_id',
        'assigned_staff_id',
        'approved_by_user_id',
        'provider_id',
        'title',
        'description',
        'priority',
        'project_type',
        'status',
        'location',
        'estimated_cost',
        'actual_cost',
        'vendor_quote_amount',
        'vendor_quote_description',
        'vendor_quote_attachments',
        'vendor_quote_valid_until',
        'vendor_contact_name',
        'vendor_contact_phone',
        'vendor_contact_email',
        'estimated_completion_date',
        'actual_completion_date',
        'requires_council_approval',
        'council_approved_at',
        'notes',
        'attachments',
        'is_recurring',
        'is_recurring_paused',
        'recurring_paused_at',
        'recurring_pause_reason',
        'recurrence_frequency',
        'recurrence_interval',
        'recurrence_start_date',
        'recurrence_end_date',
        'next_occurrence_date',
        'days_before_notification',
        'last_notified_at',
        'recurrence_metadata',
    ];

    protected $casts = [
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'vendor_quote_amount' => 'decimal:2',
        'vendor_quote_valid_until' => 'date',
        'estimated_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'requires_council_approval' => 'boolean',
        'council_approved_at' => 'datetime',
        'attachments' => 'array',
        'vendor_quote_attachments' => 'array',
        'notes' => 'array',
        'is_recurring' => 'boolean',
        'is_recurring_paused' => 'boolean',
        'recurring_paused_at' => 'datetime',
        'recurrence_start_date' => 'date',
        'recurrence_end_date' => 'date',
        'next_occurrence_date' => 'date',
        'last_notified_at' => 'datetime',
        'recurrence_metadata' => 'array',
    ];

    public const STATUS_CREATED = 'created';

    public const STATUS_EVALUATION = 'evaluation';

    public const STATUS_BUDGETED = 'budgeted';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_ASSIGNED = 'assigned';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_SUSPENDED = 'suspended';

    public const PRIORITY_LOW = 'low';

    public const PRIORITY_MEDIUM = 'medium';

    public const PRIORITY_HIGH = 'high';

    public const PRIORITY_CRITICAL = 'critical';

    public const PROJECT_TYPE_INTERNAL = 'internal';

    public const PROJECT_TYPE_EXTERNAL = 'external';

    public const RECURRENCE_DAILY = 'daily';

    public const RECURRENCE_WEEKLY = 'weekly';

    public const RECURRENCE_MONTHLY = 'monthly';

    public const RECURRENCE_QUARTERLY = 'quarterly';

    public const RECURRENCE_SEMI_ANNUAL = 'semi_annual';

    public const RECURRENCE_ANNUAL = 'annual';

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function maintenanceCategory(): BelongsTo
    {
        return $this->belongsTo(MaintenanceCategory::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function assignedStaff(): BelongsTo
    {
        return $this->belongsTo(MaintenanceStaff::class, 'assigned_staff_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function expenses(): MorphMany
    {
        return $this->morphMany(Expense::class, 'expensable');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MaintenanceRequestDocument::class);
    }

    public function getStatusBadgeColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_CREATED => 'blue',
            self::STATUS_EVALUATION => 'yellow',
            self::STATUS_BUDGETED => 'orange',
            self::STATUS_PENDING_APPROVAL => 'purple',
            self::STATUS_APPROVED => 'green',
            self::STATUS_ASSIGNED => 'indigo',
            self::STATUS_IN_PROGRESS => 'blue',
            self::STATUS_COMPLETED => 'green',
            self::STATUS_CLOSED => 'gray',
            self::STATUS_REJECTED => 'red',
            self::STATUS_SUSPENDED => 'red',
            default => 'gray',
        };
    }

    public function getPriorityBadgeColorAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'green',
            self::PRIORITY_MEDIUM => 'yellow',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_CRITICAL => 'red',
            default => 'gray',
        };
    }

    public function canBeApproved(): bool
    {
        return $this->status === self::STATUS_PENDING_APPROVAL;
    }

    public function canBeAssigned(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function canStartWork(): bool
    {
        return $this->status === self::STATUS_ASSIGNED;
    }

    public function canBeCompleted(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isExternalProject(): bool
    {
        return $this->project_type === self::PROJECT_TYPE_EXTERNAL;
    }

    public function hasVendorQuote(): bool
    {
        return $this->vendor_quote_amount !== null && $this->vendor_quote_description !== null;
    }

    public function isVendorQuoteValid(): bool
    {
        if (! $this->vendor_quote_valid_until) {
            return true; // No expiration date means it's valid
        }

        return $this->vendor_quote_valid_until->isFuture();
    }

    public function getVendorDisplayName(): string
    {
        if ($this->provider_id && $this->provider) {
            return $this->provider->name;
        }

        return $this->vendor_contact_name ?: 'Proveedor externo';
    }

    public function getVendorContactInfo(): array
    {
        if ($this->provider_id && $this->provider) {
            return [
                'name' => $this->provider->contact_name ?: $this->provider->name,
                'phone' => $this->provider->contact_phone ?: $this->provider->phone,
                'email' => $this->provider->contact_email ?: $this->provider->email,
            ];
        }

        return [
            'name' => $this->vendor_contact_name,
            'phone' => $this->vendor_contact_phone,
            'email' => $this->vendor_contact_email,
        ];
    }

    public function getNextStatus(): ?string
    {
        return match ($this->status) {
            self::STATUS_CREATED => self::STATUS_EVALUATION,
            self::STATUS_EVALUATION => self::STATUS_BUDGETED,
            self::STATUS_BUDGETED => self::STATUS_PENDING_APPROVAL,
            self::STATUS_PENDING_APPROVAL => self::STATUS_APPROVED,
            self::STATUS_APPROVED => self::STATUS_ASSIGNED,
            self::STATUS_ASSIGNED => self::STATUS_IN_PROGRESS,
            self::STATUS_IN_PROGRESS => self::STATUS_COMPLETED,
            self::STATUS_COMPLETED => self::STATUS_CLOSED,
            default => null, // No next status for closed, rejected, or suspended
        };
    }

    public function canTransitionToNextState(): bool
    {
        return $this->getNextStatus() !== null;
    }

    public function getNextStatusLabel(): ?string
    {
        $nextStatus = $this->getNextStatus();
        if (! $nextStatus) {
            return null;
        }

        $statusLabels = [
            self::STATUS_EVALUATION => 'En Evaluación',
            self::STATUS_BUDGETED => 'Presupuestada',
            self::STATUS_PENDING_APPROVAL => 'Pendiente Aprobación',
            self::STATUS_APPROVED => 'Aprobada',
            self::STATUS_ASSIGNED => 'Asignada',
            self::STATUS_IN_PROGRESS => 'En Progreso',
            self::STATUS_COMPLETED => 'Completada',
            self::STATUS_CLOSED => 'Cerrada',
        ];

        return $statusLabels[$nextStatus] ?? null;
    }

    /**
     * Calculate the next occurrence date based on recurrence settings
     */
    public function calculateNextOccurrence(): ?\Carbon\Carbon
    {
        if (! $this->is_recurring || ! $this->recurrence_frequency) {
            return null;
        }

        $baseDate = $this->next_occurrence_date ?: $this->recurrence_start_date;
        if (! $baseDate) {
            return null;
        }

        $date = \Carbon\Carbon::parse($baseDate);
        $interval = $this->recurrence_interval ?? 1;

        switch ($this->recurrence_frequency) {
            case self::RECURRENCE_DAILY:
                $date->addDays($interval);
                break;
            case self::RECURRENCE_WEEKLY:
                $date->addWeeks($interval);
                break;
            case self::RECURRENCE_MONTHLY:
                $date->addMonths($interval);
                break;
            case self::RECURRENCE_QUARTERLY:
                $date->addMonths($interval * 3);
                break;
            case self::RECURRENCE_SEMI_ANNUAL:
                $date->addMonths($interval * 6);
                break;
            case self::RECURRENCE_ANNUAL:
                $date->addYears($interval);
                break;
        }

        // Check if the next occurrence exceeds the end date
        if ($this->recurrence_end_date && $date->isAfter($this->recurrence_end_date)) {
            return null;
        }

        return $date;
    }

    /**
     * Check if maintenance is due soon (within notification window)
     */
    public function isDueSoon(): bool
    {
        if (! $this->is_recurring || ! $this->next_occurrence_date) {
            return false;
        }

        $daysBeforeNotification = $this->days_before_notification ?? 7;
        $notificationDate = now()->addDays($daysBeforeNotification);

        return $this->next_occurrence_date->isBefore($notificationDate) &&
               $this->next_occurrence_date->isFuture();
    }

    /**
     * Check if notification should be sent
     */
    public function shouldNotify(): bool
    {
        if (! $this->isDueSoon()) {
            return false;
        }

        // Don't notify if already notified recently (within 24 hours)
        if ($this->last_notified_at && $this->last_notified_at->isAfter(now()->subDay())) {
            return false;
        }

        return true;
    }

    /**
     * Mark maintenance as notified
     */
    public function markAsNotified(): void
    {
        $this->update(['last_notified_at' => now()]);
    }

    /**
     * Update the next occurrence date
     */
    public function updateNextOccurrence(): bool
    {
        $nextDate = $this->calculateNextOccurrence();

        if ($nextDate) {
            $this->update(['next_occurrence_date' => $nextDate]);

            return true;
        }

        // If no next occurrence, mark as non-recurring
        $this->update([
            'is_recurring' => false,
            'next_occurrence_date' => null,
        ]);

        return false;
    }

    /**
     * Get recurrence frequency label
     */
    public function getRecurrenceFrequencyLabel(): ?string
    {
        if (! $this->is_recurring) {
            return null;
        }

        $labels = [
            self::RECURRENCE_DAILY => 'Diario',
            self::RECURRENCE_WEEKLY => 'Semanal',
            self::RECURRENCE_MONTHLY => 'Mensual',
            self::RECURRENCE_QUARTERLY => 'Trimestral',
            self::RECURRENCE_SEMI_ANNUAL => 'Semestral',
            self::RECURRENCE_ANNUAL => 'Anual',
        ];

        $label = $labels[$this->recurrence_frequency] ?? '';

        if ($this->recurrence_interval > 1) {
            $label = "Cada {$this->recurrence_interval} ".strtolower($label);
        }

        return $label;
    }

    /**
     * Scope to get recurring maintenance due soon
     */
    public function scopeRecurringDueSoon($query, int $days = 7)
    {
        return $query->where('is_recurring', true)
            ->whereNotNull('next_occurrence_date')
            ->whereDate('next_occurrence_date', '<=', now()->addDays($days))
            ->whereDate('next_occurrence_date', '>', now());
    }

    /**
     * Scope to get maintenance that needs notification
     */
    public function scopeNeedsNotification($query)
    {
        return $query->where('is_recurring', true)
            ->where('is_recurring_paused', false)
            ->whereNotNull('next_occurrence_date')
            ->where(function ($q) {
                $q->whereNull('last_notified_at')
                    ->orWhere('last_notified_at', '<', now()->subDay());
            })
            ->whereRaw('DATE(next_occurrence_date) <= DATE_ADD(CURDATE(), INTERVAL days_before_notification DAY)')
            ->whereDate('next_occurrence_date', '>', now());
    }

    /**
     * Pause recurrence
     */
    public function pauseRecurrence(?string $reason = null): bool
    {
        if (! $this->is_recurring) {
            return false;
        }

        return $this->update([
            'is_recurring_paused' => true,
            'recurring_paused_at' => now(),
            'recurring_pause_reason' => $reason,
        ]);
    }

    /**
     * Resume recurrence
     */
    public function resumeRecurrence(): bool
    {
        if (! $this->is_recurring) {
            return false;
        }

        return $this->update([
            'is_recurring_paused' => false,
            'recurring_paused_at' => null,
            'recurring_pause_reason' => null,
        ]);
    }

    /**
     * Check if recurrence is active (not paused)
     */
    public function isRecurrenceActive(): bool
    {
        return $this->is_recurring && ! $this->is_recurring_paused;
    }
}
