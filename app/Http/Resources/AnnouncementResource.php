<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnnouncementResource extends JsonResource
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
            'title' => $this->title,
            'content' => $this->content,
            'type' => $this->type,
            'priority' => $this->priority,
            'status' => $this->status,
            'is_urgent' => $this->priority === 'high',
            'requires_confirmation' => $this->requires_confirmation,
            'is_confirmed' => $this->pivot?->is_confirmed ?? false,
            'confirmed_at' => $this->pivot?->confirmed_at?->toISOString(),
            'target_apartments' => $this->targetApartments->pluck('number')->toArray(),
            'published_at' => $this->published_at?->toISOString(),
            'expires_at' => $this->expires_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
