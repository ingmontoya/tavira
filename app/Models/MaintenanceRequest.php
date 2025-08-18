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
        'supplier_id',
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

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
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
        if ($this->supplier_id && $this->supplier) {
            return $this->supplier->name;
        }

        return $this->vendor_contact_name ?: 'Proveedor externo';
    }

    public function getVendorContactInfo(): array
    {
        if ($this->supplier_id && $this->supplier) {
            return [
                'name' => $this->supplier->contact_name ?: $this->supplier->name,
                'phone' => $this->supplier->contact_phone ?: $this->supplier->phone,
                'email' => $this->supplier->contact_email ?: $this->supplier->email,
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
}
