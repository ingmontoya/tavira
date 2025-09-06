<?php

namespace App\Events;

use App\Models\ApartmentVote;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class VoteCast implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ApartmentVote $apartmentVote,
        public User $voter
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conjunto.' . $this->apartmentVote->vote->assembly->conjunto_config_id),
            new PrivateChannel('assembly.' . $this->apartmentVote->vote->assembly_id),
            new PrivateChannel('vote.' . $this->apartmentVote->vote_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'vote.cast';
    }

    public function broadcastWith(): array
    {
        return [
            'vote_id' => $this->apartmentVote->vote_id,
            'assembly_id' => $this->apartmentVote->vote->assembly_id,
            'apartment_id' => $this->apartmentVote->apartment_id,
            'voter' => [
                'id' => $this->voter->id,
                'name' => $this->voter->name,
            ],
            'is_delegate_vote' => $this->apartmentVote->is_delegate_vote,
            'participation_stats' => $this->apartmentVote->vote->participation_stats,
            'quorum_status' => $this->apartmentVote->vote->assembly->quorum_status,
            'timestamp' => $this->apartmentVote->cast_at->toISOString(),
        ];
    }
}
