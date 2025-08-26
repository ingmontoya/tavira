<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->data['title'] ?? 'Notificación',
            'message' => $this->data['message'] ?? '',
            'data' => $this->data,
            'is_read' => ! is_null($this->read_at),
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString() ?? now()->toISOString(),
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }
}
