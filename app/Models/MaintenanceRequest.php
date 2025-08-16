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
        'title',
        'description',
        'priority',
        'status',
        'location',
        'estimated_cost',
        'actual_cost',
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
        'estimated_completion_date' => 'date',
        'actual_completion_date' => 'date',
        'requires_council_approval' => 'boolean',
        'council_approved_at' => 'datetime',
        'attachments' => 'array',
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

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function expenses(): MorphMany
    {
        return $this->morphMany(Expense::class, 'expensable');
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
        return in_array($this->status, [self::STATUS_BUDGETED, self::STATUS_PENDING_APPROVAL]);
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
}
