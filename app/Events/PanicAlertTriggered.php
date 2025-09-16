<?php

namespace App\Events;

use App\Models\PanicAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PanicAlertTriggered implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PanicAlert $panicAlert
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('panic-alerts'),
            new PrivateChannel('security-dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'panic-alert.triggered';
    }

    public function broadcastWith(): array
    {
        return [
            'alert_id' => $this->panicAlert->id,
            'user' => [
                'id' => $this->panicAlert->user_id,
                'name' => $this->panicAlert->user_display_name,
            ],
            'apartment' => [
                'id' => $this->panicAlert->apartment_id,
                'address' => $this->panicAlert->apartment_display_name,
            ],
            'location' => [
                'lat' => $this->panicAlert->lat,
                'lng' => $this->panicAlert->lng,
                'string' => $this->panicAlert->location_string,
            ],
            'status' => $this->panicAlert->status,
            'timestamp' => $this->panicAlert->created_at->toISOString(),
            'time_ago' => $this->panicAlert->created_at->diffForHumans(),
        ];
    }
}