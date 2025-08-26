<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
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
            'payment_number' => $this->payment_number,
            'payment_date' => $this->payment_date?->toISOString() ?? now()->toISOString(),
            'amount' => $this->amount,
            'method' => $this->method,
            'reference' => $this->reference,
            'status' => $this->status,
            'notes' => $this->notes,
            'apartment' => [
                'number' => $this->apartment->number,
                'tower' => $this->apartment->tower,
            ],
            'applications' => PaymentApplicationResource::collection($this->whenLoaded('applications')),
            'created_at' => $this->created_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
