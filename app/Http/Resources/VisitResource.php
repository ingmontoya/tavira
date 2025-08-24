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
            'visitor_document' => $this->visitor_document,
            'visitor_phone' => $this->visitor_phone,
            'visitor_email' => $this->visitor_email,
            'visit_date' => $this->visit_date->toISOString(),
            'expected_arrival' => $this->expected_arrival?->format('H:i'),
            'expected_departure' => $this->expected_departure?->format('H:i'),
            'purpose' => $this->purpose,
            'status' => $this->status,
            'notes' => $this->notes,
            'is_recurring' => $this->is_recurring,
            'recurrence_pattern' => $this->recurrence_pattern,
            'access_code' => $this->access_code,
            'qr_code_url' => $this->when($this->access_code, "/api/visits/{$this->id}/qr-code"),
            'apartment' => [
                'number' => $this->apartment->number,
                'tower' => $this->apartment->tower,
            ],
            'actual_arrival' => $this->actual_arrival?->toISOString(),
            'actual_departure' => $this->actual_departure?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
