<?php

namespace App\Events;

use App\Models\Assembly;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssemblyCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Assembly $assembly,
        public User $creator
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('conjunto.'.$this->assembly->conjunto_config_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'assembly.created';
    }

    public function broadcastWith(): array
    {
        return [
            'assembly' => [
                'id' => $this->assembly->id,
                'title' => $this->assembly->title,
                'type' => $this->assembly->type,
                'scheduled_at' => $this->assembly->scheduled_at?->toISOString(),
                'status' => $this->assembly->status,
                'status_badge' => $this->assembly->status_badge,
            ],
            'creator' => [
                'id' => $this->creator->id,
                'name' => $this->creator->name,
            ],
            'timestamp' => now()->toISOString(),
        ];
    }
}
