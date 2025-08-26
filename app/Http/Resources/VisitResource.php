<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VisitResource extends JsonResource
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
            'visitor_name' => $this->visitor_name,
            'visitor_document_type' => $this->visitor_document_type,
            'visitor_document_number' => $this->visitor_document_number,
            'visitor_phone' => $this->visitor_phone,
            'visit_reason' => $this->visit_reason,
            'valid_from' => $this->valid_from?->toISOString() ?? now()->toISOString(),
            'valid_until' => $this->valid_until?->toISOString() ?? now()->toISOString(),
            'status' => $this->status,
            'qr_code' => $this->qr_code,
            'qr_code_url' => $this->when($this->qr_code, "/api/visits/{$this->id}/qr-code"),
            'apartment' => [
                'number' => $this->apartment->number,
                'tower' => $this->apartment->tower,
            ],
            'entry_time' => $this->entry_time?->toISOString(),
            'exit_time' => $this->exit_time?->toISOString(),
            'security_notes' => $this->security_notes,
            'authorized_by' => $this->whenLoaded('authorizer', [
                'id' => $this->authorizer?->id,
                'name' => $this->authorizer?->name,
            ]),
            'created_by' => $this->whenLoaded('creator', [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
            'created_at' => $this->created_at?->toISOString() ?? now()->toISOString(),
            'updated_at' => $this->updated_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}