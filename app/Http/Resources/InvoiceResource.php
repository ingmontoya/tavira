<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'invoice_number' => $this->invoice_number,
            'period_year' => $this->billing_period_year,
            'period_month' => $this->billing_period_month,
            'issue_date' => $this->billing_date?->toISOString(),
            'due_date' => $this->due_date?->toISOString(),
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'total_amount' => $this->total_amount,
            'paid_amount' => $this->paid_amount ?? 0,
            'balance' => $this->total_amount - ($this->paid_amount ?? 0),
            'is_paid' => $this->status === 'paid',
            'is_overdue' => $this->due_date?->isPast() && $this->status !== 'paid',
            'items' => InvoiceItemResource::collection($this->whenLoaded('items')),
            'apartment' => [
                'number' => $this->apartment->number,
                'tower' => $this->apartment->tower,
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
