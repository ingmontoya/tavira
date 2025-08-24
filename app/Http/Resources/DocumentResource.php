<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
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
            'original_name' => $this->original_name,
            'file_path' => $this->file_path,
            'file_size' => $this->file_size,
            'mime_type' => $this->mime_type,
            'download_url' => $this->when($this->file_path, url($this->file_path)),
            'is_image' => str_starts_with($this->mime_type ?? '', 'image/'),
            'is_pdf' => $this->mime_type === 'application/pdf',
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
