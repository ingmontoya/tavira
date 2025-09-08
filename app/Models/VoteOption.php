<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VoteOption extends Model
{
    protected $fillable = [
        'vote_id',
        'title',
        'description',
        'order',
        'value',
        'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }

    public function apartmentVotes(): HasMany
    {
        return $this->hasMany(ApartmentVote::class);
    }

    public function getVoteCountAttribute(): int
    {
        return $this->apartmentVotes()->count();
    }

    public function getVotePercentageAttribute(): float
    {
        $totalVotes = $this->vote->apartmentVotes()->count();
        
        if ($totalVotes === 0) {
            return 0;
        }

        return round(($this->vote_count / $totalVotes) * 100, 2);
    }

    public function scopeForVote($query, int $voteId)
    {
        return $query->where('vote_id', $voteId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}
