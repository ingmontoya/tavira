<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MaintenanceRequestResource extends JsonResource
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
            'request_number' => $this->request_number,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->category,
            'priority' => $this->priority,
            'status' => $this->status,
            'is_urgent' => $this->priority === 'high',
            'location' => $this->location,
            'requested_date' => $this->requested_date?->toISOString(),
            'scheduled_date' => $this->scheduled_date?->toISOString(),
            'completed_date' => $this->completed_date?->toISOString(),
            'estimated_cost' => $this->estimated_cost,
            'actual_cost' => $this->actual_cost,
            'notes' => $this->notes,
            'resolution_notes' => $this->resolution_notes,
            'apartment' => [
                'number' => $this->apartment->number,
                'tower' => $this->apartment->tower,
            ],
            'documents' => DocumentResource::collection($this->whenLoaded('documents')),
            'assigned_to' => $this->whenLoaded('assignedTo', [
                'id' => $this->assignedTo?->id,
                'name' => $this->assignedTo?->name,
            ]),
            'created_at' => $this->created_at?->toISOString() ?? now()->toISOString(),
            'updated_at' => $this->updated_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
