<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Assembly extends Model
{
    protected $fillable = [
        'conjunto_config_id',
        'title',
        'description',
        'type',
        'status',
        'scheduled_at',
        'started_at',
        'ended_at',
        'required_quorum_percentage',
        'documents',
        'meeting_notes',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'documents' => 'array',
        'metadata' => 'array',
    ];

    protected $appends = [
        'duration_minutes',
        'is_active',
        'can_vote',
        'quorum_status',
        'status_badge',
    ];

    public function conjuntoConfig(): BelongsTo
    {
        return $this->belongsTo(ConjuntoConfig::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    public function delegates(): HasMany
    {
        return $this->hasMany(VoteDelegate::class);
    }

    public function getDurationMinutesAttribute(): ?int
    {
        if ($this->started_at && $this->ended_at) {
            return $this->started_at->diffInMinutes($this->ended_at);
        }
        
        return null;
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'in_progress';
    }

    public function getCanVoteAttribute(): bool
    {
        return in_array($this->status, ['in_progress']);
    }

    public function getQuorumStatusAttribute(): array
    {
        $totalApartments = Apartment::where('conjunto_config_id', $this->conjunto_config_id)->count();
        $participatingApartments = $this->getParticipatingApartmentsCount();
        $quorumPercentage = $totalApartments > 0 ? ($participatingApartments / $totalApartments) * 100 : 0;
        $hasQuorum = $quorumPercentage >= $this->required_quorum_percentage;

        return [
            'total_apartments' => $totalApartments,
            'participating_apartments' => $participatingApartments,
            'quorum_percentage' => round($quorumPercentage, 2),
            'required_quorum_percentage' => $this->required_quorum_percentage,
            'has_quorum' => $hasQuorum,
        ];
    }

    public function getStatusBadgeAttribute(): array
    {
        return match ($this->status) {
            'scheduled' => ['text' => 'Programada', 'class' => 'bg-blue-100 text-blue-800'],
            'in_progress' => ['text' => 'En Curso', 'class' => 'bg-green-100 text-green-800'],
            'closed' => ['text' => 'Cerrada', 'class' => 'bg-gray-100 text-gray-800'],
            'cancelled' => ['text' => 'Cancelada', 'class' => 'bg-red-100 text-red-800'],
            default => ['text' => 'Unknown', 'class' => 'bg-gray-100 text-gray-800'],
        };
    }

    protected function getParticipatingApartmentsCount(): int
    {
        return ApartmentVote::whereIn('vote_id', $this->votes->pluck('id'))
            ->distinct('apartment_id')
            ->count('apartment_id');
    }

    public function scopeForConjunto($query, int $conjuntoId)
    {
        return $query->where('conjunto_config_id', $conjuntoId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeScheduledBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('scheduled_at', [$startDate, $endDate]);
    }

    public function start(): bool
    {
        if ($this->status !== 'scheduled') {
            return false;
        }

        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);

        return true;
    }

    public function close(string $meetingNotes = null): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $this->update([
            'status' => 'closed',
            'ended_at' => now(),
            'meeting_notes' => $meetingNotes ?: $this->meeting_notes,
        ]);

        return true;
    }

    public function cancel(): bool
    {
        if (!in_array($this->status, ['scheduled', 'in_progress'])) {
            return false;
        }

        $this->update(['status' => 'cancelled']);

        return true;
    }
}
