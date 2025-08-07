<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvoiceEmailDelivery extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'batch_id',
        'invoice_id',
        'apartment_id',
        'recipient_email',
        'recipient_name',
        'apartment_number',
        'email_subject',
        'email_template_used',
        'email_variables',
        'attachments',
        'status',
        'queued_at',
        'sent_at',
        'delivered_at',
        'opened_at',
        'clicked_at',
        'bounced_at',
        'failed_at',
        'failure_reason',
        'bounce_type',
        'smtp_response',
        'provider',
        'provider_message_id',
        'provider_metadata',
        'retry_count',
        'last_retry_at',
        'next_retry_at',
        'cost',
    ];

    protected $casts = [
        'email_variables' => 'array',
        'attachments' => 'array',
        'smtp_response' => 'array',
        'provider_metadata' => 'array',
        'queued_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
        'failed_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'cost' => 'decimal:4',
    ];

    protected $appends = [
        'status_label',
        'status_badge',
        'delivery_duration',
        'can_retry',
        'failure_category',
    ];

    // Relationships
    public function batch(): BelongsTo
    {
        return $this->belongsTo(InvoiceEmailBatch::class, 'batch_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    // Scopes
    public function scopeByStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeSending(Builder $query): Builder
    {
        return $query->where('status', 'sending');
    }

    public function scopeSent(Builder $query): Builder
    {
        return $query->whereIn('status', ['sent', 'delivered', 'opened', 'clicked']);
    }

    public function scopeDelivered(Builder $query): Builder
    {
        return $query->whereIn('status', ['delivered', 'opened', 'clicked']);
    }

    public function scopeOpened(Builder $query): Builder
    {
        return $query->whereIn('status', ['opened', 'clicked']);
    }

    public function scopeClicked(Builder $query): Builder
    {
        return $query->where('status', 'clicked');
    }

    public function scopeFailed(Builder $query): Builder
    {
        return $query->whereIn('status', ['failed', 'bounced', 'rejected']);
    }

    public function scopeBounced(Builder $query): Builder
    {
        return $query->where('status', 'bounced');
    }

    public function scopeComplained(Builder $query): Builder
    {
        return $query->where('status', 'complained');
    }

    public function scopeUnsubscribed(Builder $query): Builder
    {
        return $query->where('status', 'unsubscribed');
    }

    public function scopeByBatch(Builder $query, int $batchId): Builder
    {
        return $query->where('batch_id', $batchId);
    }

    public function scopeByApartment(Builder $query, int $apartmentId): Builder
    {
        return $query->where('apartment_id', $apartmentId);
    }

    public function scopeByRecipient(Builder $query, string $email): Builder
    {
        return $query->where('recipient_email', $email);
    }

    public function scopeByProvider(Builder $query, string $provider): Builder
    {
        return $query->where('provider', $provider);
    }

    public function scopeNeedingRetry(Builder $query): Builder
    {
        return $query->whereIn('status', ['failed', 'bounced'])
            ->where('bounce_type', '!=', 'hard')
            ->where('retry_count', '<', 3)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            });
    }

    public function scopeSentBetween(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->whereBetween('sent_at', [$start, $end]);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'sending' => 'Enviando',
            'sent' => 'Enviado',
            'delivered' => 'Entregado',
            'opened' => 'Abierto',
            'clicked' => 'Clic realizado',
            'bounced' => 'Rebotado',
            'failed' => 'Fallido',
            'rejected' => 'Rechazado',
            'complained' => 'Reportado como spam',
            'unsubscribed' => 'Desuscrito',
            default => 'Desconocido',
        };
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'pending' => ['text' => 'Pendiente', 'class' => 'bg-gray-100 text-gray-800'],
            'sending' => ['text' => 'Enviando', 'class' => 'bg-blue-100 text-blue-800'],
            'sent' => ['text' => 'Enviado', 'class' => 'bg-blue-100 text-blue-800'],
            'delivered' => ['text' => 'Entregado', 'class' => 'bg-green-100 text-green-800'],
            'opened' => ['text' => 'Abierto', 'class' => 'bg-green-100 text-green-800'],
            'clicked' => ['text' => 'Clic realizado', 'class' => 'bg-green-100 text-green-800'],
            'bounced' => ['text' => 'Rebotado', 'class' => 'bg-yellow-100 text-yellow-800'],
            'failed' => ['text' => 'Fallido', 'class' => 'bg-red-100 text-red-800'],
            'rejected' => ['text' => 'Rechazado', 'class' => 'bg-red-100 text-red-800'],
            'complained' => ['text' => 'Spam', 'class' => 'bg-red-100 text-red-800'],
            'unsubscribed' => ['text' => 'Desuscrito', 'class' => 'bg-gray-100 text-gray-800'],
            default => ['text' => 'Desconocido', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function getDeliveryDurationAttribute(): ?string
    {
        if (! $this->sent_at || ! $this->delivered_at) {
            return null;
        }

        return $this->sent_at->diffForHumans($this->delivered_at, true);
    }

    public function getCanRetryAttribute(): bool
    {
        return in_array($this->status, ['failed', 'bounced'])
            && $this->bounce_type !== 'hard'
            && $this->retry_count < 3;
    }

    public function getFailureCategoryAttribute(): ?string
    {
        if (! $this->failure_reason) {
            return null;
        }

        $reason = strtolower($this->failure_reason);

        if (str_contains($reason, 'invalid') || str_contains($reason, 'not found')) {
            return 'invalid_email';
        }

        if (str_contains($reason, 'spam') || str_contains($reason, 'blocked')) {
            return 'spam_blocked';
        }

        if (str_contains($reason, 'quota') || str_contains($reason, 'limit')) {
            return 'rate_limit';
        }

        if (str_contains($reason, 'timeout') || str_contains($reason, 'connection')) {
            return 'network';
        }

        return 'other';
    }

    // Status Update Methods
    public function markAsQueued(): void
    {
        $this->update([
            'status' => 'pending',
            'queued_at' => now(),
        ]);
    }

    public function markAsSending(): void
    {
        $this->update([
            'status' => 'sending',
        ]);
    }

    public function markAsSent(?string $provider = null, ?string $messageId = null, array $metadata = []): void
    {
        $this->update([
            'status' => 'sent',
            'sent_at' => now(),
            'provider' => $provider,
            'provider_message_id' => $messageId,
            'provider_metadata' => $metadata,
        ]);
    }

    public function markAsDelivered(array $metadata = []): void
    {
        $this->update([
            'status' => 'delivered',
            'delivered_at' => now(),
            'provider_metadata' => array_merge($this->provider_metadata ?? [], $metadata),
        ]);
    }

    public function markAsOpened(array $metadata = []): void
    {
        $this->update([
            'status' => 'opened',
            'opened_at' => now(),
            'provider_metadata' => array_merge($this->provider_metadata ?? [], $metadata),
        ]);
    }

    public function markAsClicked(array $metadata = []): void
    {
        $this->update([
            'status' => 'clicked',
            'clicked_at' => now(),
            'provider_metadata' => array_merge($this->provider_metadata ?? [], $metadata),
        ]);
    }

    public function markAsBounced(string $bounceType, string $reason, array $metadata = []): void
    {
        $this->update([
            'status' => 'bounced',
            'bounce_type' => $bounceType,
            'bounced_at' => now(),
            'failure_reason' => $reason,
            'provider_metadata' => array_merge($this->provider_metadata ?? [], $metadata),
        ]);

        $this->scheduleRetryIfEligible();
    }

    public function markAsFailed(string $reason, array $smtpResponse = [], array $metadata = []): void
    {
        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $reason,
            'smtp_response' => $smtpResponse,
            'provider_metadata' => array_merge($this->provider_metadata ?? [], $metadata),
        ]);

        $this->scheduleRetryIfEligible();
    }

    public function markAsRejected(string $reason, array $metadata = []): void
    {
        $this->update([
            'status' => 'rejected',
            'failed_at' => now(),
            'failure_reason' => $reason,
            'provider_metadata' => array_merge($this->provider_metadata ?? [], $metadata),
        ]);
    }

    public function markAsComplained(array $metadata = []): void
    {
        $this->update([
            'status' => 'complained',
            'provider_metadata' => array_merge($this->provider_metadata ?? [], $metadata),
        ]);
    }

    public function markAsUnsubscribed(array $metadata = []): void
    {
        $this->update([
            'status' => 'unsubscribed',
            'provider_metadata' => array_merge($this->provider_metadata ?? [], $metadata),
        ]);
    }

    public function scheduleRetry(): void
    {
        if (! $this->can_retry) {
            return;
        }

        $this->increment('retry_count');

        // Exponential backoff: 5min, 30min, 2hours
        $delays = [5, 30, 120]; // minutes
        $delay = $delays[min($this->retry_count - 1, count($delays) - 1)];

        $this->update([
            'last_retry_at' => now(),
            'next_retry_at' => now()->addMinutes($delay),
            'status' => 'pending',
        ]);
    }

    private function scheduleRetryIfEligible(): void
    {
        if ($this->can_retry) {
            $this->scheduleRetry();
        }
    }
}
