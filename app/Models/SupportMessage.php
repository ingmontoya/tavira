<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'support_ticket_id',
        'user_id',
        'message',
        'is_admin_reply',
        'attachments',
        'read_at',
    ];

    protected $casts = [
        'is_admin_reply' => 'boolean',
        'attachments' => 'array',
        'read_at' => 'datetime',
    ];

    public function supportTicket(): BelongsTo
    {
        return $this->belongsTo(SupportTicket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeByTicket($query, int $ticketId)
    {
        return $query->where('support_ticket_id', $ticketId);
    }

    public function scopeAdminReplies($query)
    {
        return $query->where('is_admin_reply', true);
    }

    public function scopeUserMessages($query)
    {
        return $query->where('is_admin_reply', false);
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function isRead(): bool
    {
        return ! is_null($this->read_at);
    }

    public function markAsRead(): void
    {
        if (! $this->isRead()) {
            $this->update(['read_at' => now()]);
        }
    }

    public function isFromAdmin(): bool
    {
        return $this->is_admin_reply;
    }

    public function hasAttachments(): bool
    {
        return ! empty($this->attachments);
    }
}
