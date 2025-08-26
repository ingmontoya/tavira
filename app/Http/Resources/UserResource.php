<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'email_verified_at' => $this->email_verified_at?->toISOString(),
            'role' => $this->roles->first()?->name ?? 'resident',
            'apartment' => $this->when(
                $this->relationLoaded('resident'),
                new ApartmentResource($this->resident?->apartment)
            ),
            'created_at' => $this->created_at?->toISOString() ?? now()->toISOString(),
            'updated_at' => $this->updated_at?->toISOString() ?? now()->toISOString(),
        ];
    }
}
