<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vote extends Model
{
    protected $fillable = [
        'assembly_id',
        'title',
        'description',
        'type',
        'status',
        'opens_at',
        'closes_at',
        'required_quorum_percentage',
        'required_approval_percentage',
        'allows_abstention',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'opens_at' => 'datetime',
        'closes_at' => 'datetime',
        'allows_abstention' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = [
        'is_active',
        'is_closed',
        'can_vote',
        'results',
        'participation_stats',
        'status_badge',
    ];

    public function assembly(): BelongsTo
    {
        return $this->belongsTo(Assembly::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function options(): HasMany
    {
        return $this->hasMany(VoteOption::class)->orderBy('order');
    }

    public function apartmentVotes(): HasMany
    {
        return $this->hasMany(ApartmentVote::class);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active' &&
               now()->between($this->opens_at, $this->closes_at);
    }

    public function getIsClosedAttribute(): bool
    {
        return $this->status === 'closed' ||
               ($this->closes_at && now()->gt($this->closes_at));
    }

    public function getCanVoteAttribute(): bool
    {
        return $this->is_active && $this->assembly->can_vote;
    }

    public function getResultsAttribute(): array
    {
        $results = [];
        $totalVotes = $this->apartmentVotes()->count();
        $totalWeight = $this->apartmentVotes()->sum('weight');

        if ($this->type === 'yes_no') {
            $results = [
                'yes' => [
                    'count' => $this->apartmentVotes()->where('choice', 'yes')->count(),
                    'weight' => $this->apartmentVotes()->where('choice', 'yes')->sum('weight'),
                    'percentage' => 0,
                ],
                'no' => [
                    'count' => $this->apartmentVotes()->where('choice', 'no')->count(),
                    'weight' => $this->apartmentVotes()->where('choice', 'no')->sum('weight'),
                    'percentage' => 0,
                ],
                'abstain' => [
                    'count' => $this->apartmentVotes()->where('choice', 'abstain')->count(),
                    'weight' => $this->apartmentVotes()->where('choice', 'abstain')->sum('weight'),
                    'percentage' => 0,
                ],
            ];

            if ($totalWeight > 0) {
                foreach ($results as $choice => &$data) {
                    $data['percentage'] = round(($data['weight'] / $totalWeight) * 100, 2);
                }
            }
        } elseif ($this->type === 'multiple_choice') {
            foreach ($this->options as $option) {
                $count = $this->apartmentVotes()->where('vote_option_id', $option->id)->count();
                $weight = $this->apartmentVotes()->where('vote_option_id', $option->id)->sum('weight');

                $results[] = [
                    'option_id' => $option->id,
                    'option_title' => $option->title,
                    'count' => $count,
                    'weight' => $weight,
                    'percentage' => $totalWeight > 0 ? round(($weight / $totalWeight) * 100, 2) : 0,
                ];
            }
        } elseif ($this->type === 'quantitative') {
            $results = [
                'total_value' => $this->apartmentVotes()->sum('quantitative_value'),
                'average_value' => $totalVotes > 0 ? $this->apartmentVotes()->avg('quantitative_value') : 0,
                'min_value' => $this->apartmentVotes()->min('quantitative_value') ?: 0,
                'max_value' => $this->apartmentVotes()->max('quantitative_value') ?: 0,
                'votes_count' => $totalVotes,
            ];
        }

        return $results;
    }

    public function getParticipationStatsAttribute(): array
    {
        $totalApartments = Apartment::where('conjunto_config_id', $this->assembly->conjunto_config_id)->count();
        $votedApartments = $this->apartmentVotes()->count();
        $participationPercentage = $totalApartments > 0 ? ($votedApartments / $totalApartments) * 100 : 0;
        $hasQuorum = $participationPercentage >= $this->required_quorum_percentage;

        return [
            'total_apartments' => $totalApartments,
            'voted_apartments' => $votedApartments,
            'participation_percentage' => round($participationPercentage, 2),
            'required_quorum_percentage' => $this->required_quorum_percentage,
            'has_quorum' => $hasQuorum,
        ];
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'draft' => ['text' => 'Borrador', 'class' => 'bg-gray-100 text-gray-800'],
            'active' => ['text' => 'Activa', 'class' => 'bg-green-100 text-green-800'],
            'closed' => ['text' => 'Cerrada', 'class' => 'bg-blue-100 text-blue-800'],
            default => ['text' => 'Unknown', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('opens_at', '<=', now())
            ->where('closes_at', '>=', now());
    }

    public function scopeForAssembly($query, int $assemblyId)
    {
        return $query->where('assembly_id', $assemblyId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function activate(): bool
    {
        if ($this->status !== 'draft') {
            return false;
        }

        $this->update([
            'status' => 'active',
            'opens_at' => $this->opens_at ?: now(),
        ]);

        return true;
    }

    public function close(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $this->update([
            'status' => 'closed',
            'closes_at' => now(),
        ]);

        return true;
    }

    public function hasUserVoted(int $apartmentId): bool
    {
        return $this->apartmentVotes()->where('apartment_id', $apartmentId)->exists();
    }

    public function getUserVote(int $apartmentId): ?ApartmentVote
    {
        return $this->apartmentVotes()->where('apartment_id', $apartmentId)->first();
    }

    public function isApproved(): bool
    {
        if ($this->type !== 'yes_no') {
            return false;
        }

        $results = $this->results;
        $yesPercentage = $results['yes']['percentage'] ?? 0;

        return $yesPercentage >= $this->required_approval_percentage;
    }
}
