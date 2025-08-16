<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceEmailBatch extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'batch_number',
        'name',
        'description',
        'filters',
        'email_settings',
        'total_invoices',
        'total_recipients',
        'emails_sent',
        'emails_delivered',
        'emails_failed',
        'emails_opened',
        'emails_clicked',
        'status',
        'scheduled_at',
        'started_at',
        'completed_at',
        'cancelled_at',
        'failure_reason',
        'processing_log',
        'estimated_cost',
        'actual_cost',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'filters' => 'array',
        'email_settings' => 'array',
        'processing_log' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'estimated_cost' => 'decimal:4',
        'actual_cost' => 'decimal:4',
    ];

    protected $appends = [
        'status_label',
        'status_badge',
        'progress_percentage',
        'delivery_rate',
        'open_rate',
        'click_rate',
        'duration',
        'is_editable',
        'can_be_cancelled',
        'can_be_restarted',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(InvoiceEmailDelivery::class, 'batch_id');
    }

    public function invoices()
    {
        return $this->hasManyThrough(
            Invoice::class,
            InvoiceEmailDelivery::class,
            'batch_id',
            'id',
            'id',
            'invoice_id'
        )->distinct();
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'failed');
    }

    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', ['scheduled', 'processing']);
    }

    public function scopeFinished(Builder $query): Builder
    {
        return $query->whereIn('status', ['completed', 'failed', 'cancelled']);
    }

    public function scopeByCreator(Builder $query, int $userId): Builder
    {
        return $query->where('created_by', $userId);
    }

    public function scopeScheduledBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('scheduled_at', [$start, $end]);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'draft' => 'Borrador',
            'scheduled' => 'Programado',
            'processing' => 'Procesando',
            'completed' => 'Completado',
            'failed' => 'Fallido',
            'cancelled' => 'Cancelado',
            default => 'Desconocido',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'draft' => ['text' => 'Borrador', 'class' => 'bg-gray-100 text-gray-800'],
            'scheduled' => ['text' => 'Programado', 'class' => 'bg-blue-100 text-blue-800'],
            'processing' => ['text' => 'Procesando', 'class' => 'bg-yellow-100 text-yellow-800'],
            'completed' => ['text' => 'Completado', 'class' => 'bg-green-100 text-green-800'],
            'failed' => ['text' => 'Fallido', 'class' => 'bg-red-100 text-red-800'],
            'cancelled' => ['text' => 'Cancelado', 'class' => 'bg-gray-100 text-gray-800'],
            default => ['text' => 'Desconocido', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getProgressPercentageAttribute(): float
    {
        $totalRecipients = $this->total_recipients ?? 0;
        $emailsSent = $this->emails_sent ?? 0;
        
        if ($totalRecipients === 0) {
            return 0;
        }

        return round(($emailsSent / $totalRecipients) * 100, 2);
    }

    public function getDeliveryRateAttribute(): float
    {
        $emailsSent = $this->emails_sent ?? 0;
        $emailsDelivered = $this->emails_delivered ?? 0;
        
        if ($emailsSent === 0) {
            return 0;
        }

        return round(($emailsDelivered / $emailsSent) * 100, 2);
    }

    public function getOpenRateAttribute(): float
    {
        $emailsDelivered = $this->emails_delivered ?? 0;
        $emailsOpened = $this->emails_opened ?? 0;
        
        if ($emailsDelivered === 0) {
            return 0;
        }

        return round(($emailsOpened / $emailsDelivered) * 100, 2);
    }

    public function getClickRateAttribute(): float
    {
        $emailsOpened = $this->emails_opened ?? 0;
        $emailsClicked = $this->emails_clicked ?? 0;
        
        if ($emailsOpened === 0) {
            return 0;
        }

        return round(($emailsClicked / $emailsOpened) * 100, 2);
    }

    public function getDurationAttribute(): ?string
    {
        if (! $this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? now();

        return $this->started_at->diffForHumans($end, true);
    }

    public function getIsEditableAttribute(): bool
    {
        return $this->status === 'draft';
    }

    public function getCanBeCancelledAttribute(): bool
    {
        return in_array($this->status, ['scheduled', 'processing']);
    }

    public function getCanBeRestartedAttribute(): bool
    {
        return in_array($this->status, ['failed', 'cancelled']);
    }

    // Helper Methods
    public function updateStatistics(): void
    {
        $stats = $this->deliveries()
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('sent', 'delivered', 'opened', 'clicked') THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'delivered' OR status = 'opened' OR status = 'clicked' THEN 1 ELSE 0 END) as delivered,
                SUM(CASE WHEN status IN ('failed', 'bounced', 'rejected') THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status IN ('opened', 'clicked') THEN 1 ELSE 0 END) as opened,
                SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked
            ")
            ->first();

        $this->update([
            'total_recipients' => $stats->total,
            'emails_sent' => $stats->sent,
            'emails_delivered' => $stats->delivered,
            'emails_failed' => $stats->failed,
            'emails_opened' => $stats->opened,
            'emails_clicked' => $stats->clicked,
        ]);
    }

    public function markAsScheduled(Carbon $scheduledAt): void
    {
        $this->update([
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);
    }

    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->updateStatistics();

        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'status' => 'failed',
            'failure_reason' => $reason,
            'completed_at' => now(),
        ]);
    }

    public function markAsCancelled(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'failure_reason' => $reason,
        ]);
    }

    public function addToProcessingLog(string $message, array $data = []): void
    {
        $log = $this->processing_log ?? [];
        $log[] = [
            'timestamp' => now()->toISOString(),
            'message' => $message,
            'data' => $data,
        ];

        $this->update(['processing_log' => $log]);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($batch) {
            if (empty($batch->batch_number)) {
                $batch->batch_number = self::generateBatchNumber();
            }
        });
    }

    private static function generateBatchNumber(): string
    {
        $date = now()->format('Ymd');
        $lastBatch = self::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastBatch ? ((int) substr($lastBatch->batch_number, -4)) + 1 : 1;

        return sprintf('BATCH-%s-%04d', $date, $sequence);
    }
}
