<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class ApartmentVote extends Model
{
    protected $fillable = [
        'vote_id',
        'apartment_id',
        'vote_option_id',
        'quantitative_value',
        'choice',
        'encrypted_vote',
        'weight',
        'cast_by_user_id',
        'on_behalf_of_user_id',
        'cast_at',
        'metadata',
    ];

    protected $casts = [
        'quantitative_value' => 'decimal:2',
        'weight' => 'decimal:4',
        'cast_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'is_delegate_vote',
        'display_choice',
    ];

    public function vote(): BelongsTo
    {
        return $this->belongsTo(Vote::class);
    }

    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    public function voteOption(): BelongsTo
    {
        return $this->belongsTo(VoteOption::class);
    }

    public function castByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cast_by_user_id');
    }

    public function onBehalfOfUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'on_behalf_of_user_id');
    }

    public function getIsDelegateVoteAttribute(): bool
    {
        return $this->on_behalf_of_user_id !== null;
    }

    public function getDisplayChoiceAttribute(): string
    {
        if ($this->vote->type === 'yes_no') {
            return match ($this->choice) {
                'yes' => 'Sí',
                'no' => 'No',
                'abstain' => 'Abstención',
                default => 'No definido',
            };
        } elseif ($this->vote->type === 'multiple_choice' && $this->voteOption) {
            return $this->voteOption->title;
        } elseif ($this->vote->type === 'quantitative') {
            return number_format($this->quantitative_value, 2);
        }

        return 'No definido';
    }

    public function encryptVoteData(array $voteData): void
    {
        $this->encrypted_vote = Crypt::encrypt($voteData);
    }

    public function decryptVoteData(): ?array
    {
        if (! $this->encrypted_vote) {
            return null;
        }

        try {
            return Crypt::decrypt($this->encrypted_vote);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function scopeForVote($query, int $voteId)
    {
        return $query->where('vote_id', $voteId);
    }

    public function scopeForApartment($query, int $apartmentId)
    {
        return $query->where('apartment_id', $apartmentId);
    }

    public function scopeByChoice($query, string $choice)
    {
        return $query->where('choice', $choice);
    }

    public function scopeByOption($query, int $optionId)
    {
        return $query->where('vote_option_id', $optionId);
    }

    public function scopeDelegateVotes($query)
    {
        return $query->whereNotNull('on_behalf_of_user_id');
    }

    public function scopeDirectVotes($query)
    {
        return $query->whereNull('on_behalf_of_user_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($apartmentVote) {
            if (! $apartmentVote->cast_at) {
                $apartmentVote->cast_at = now();
            }
        });
    }
}
