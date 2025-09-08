<?php

namespace App\Events;

use App\Models\Assembly;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssemblyClosed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Assembly $assembly,
        public ?User $closedBy = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conjunto.' . $this->assembly->conjunto_config_id),
            new PrivateChannel('assembly.' . $this->assembly->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'assembly.closed';
    }

    public function broadcastWith(): array
    {
        $votingResults = [];
        
        foreach ($this->assembly->votes as $vote) {
            $votingResults[] = [
                'id' => $vote->id,
                'title' => $vote->title,
                'type' => $vote->type,
                'results' => $vote->results,
                'participation_stats' => $vote->participation_stats,
                'is_approved' => $vote->isApproved(),
                'status' => $vote->status,
            ];
        }

        return [
            'assembly' => [
                'id' => $this->assembly->id,
                'title' => $this->assembly->title,
                'type' => $this->assembly->type,
                'status' => $this->assembly->status,
                'status_badge' => $this->assembly->status_badge,
                'started_at' => $this->assembly->started_at?->toISOString(),
                'ended_at' => $this->assembly->ended_at?->toISOString(),
                'duration_minutes' => $this->assembly->duration_minutes,
                'quorum_status' => $this->assembly->quorum_status,
            ],
            'voting_results' => $votingResults,
            'closed_by' => $this->closedBy ? [
                'id' => $this->closedBy->id,
                'name' => $this->closedBy->name,
            ] : null,
            'timestamp' => now()->toISOString(),
        ];
    }
}
