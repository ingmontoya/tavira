<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementConfirmation extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_id',
        'user_id',
        'read_at',
        'confirmed_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
        'confirmed_at' => 'datetime',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIsReadAttribute(): bool
    {
        return ! is_null($this->read_at);
    }

    public function getIsConfirmedAttribute(): bool
    {
        return ! is_null($this->confirmed_at);
    }
}