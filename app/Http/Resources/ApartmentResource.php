<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApartmentResource extends JsonResource
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
            'number' => $this->number,
            'tower' => $this->tower,
            'floor' => $this->floor,
            'type' => [
                'id' => $this->apartmentType?->id,
                'name' => $this->apartmentType?->name,
                'bedrooms' => $this->apartmentType?->bedrooms,
                'bathrooms' => $this->apartmentType?->bathrooms,
                'area' => $this->apartmentType?->area,
                'description' => $this->apartmentType?->description,
            ],
            'status' => $this->status,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
