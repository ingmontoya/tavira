<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkOrderItem extends Model
{
    protected $fillable = [
        'work_order_id',
        'item_type',
        'name',
        'description',
        'quantity',
        'unit_cost',
        'total_cost',
        'supplier',
        'is_completed',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'is_completed' => 'boolean',
    ];

    public const TYPE_MATERIAL = 'material';

    public const TYPE_TOOL = 'tool';

    public const TYPE_SERVICE = 'service';

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }
}
