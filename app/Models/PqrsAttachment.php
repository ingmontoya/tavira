<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PqrsAttachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'pqrs_id',
        'filename',
        'original_filename',
        'mime_type',
        'file_size',
        'file_path',
        'type',
        'uploaded_by',
    ];

    public function pqrs(): BelongsTo
    {
        return $this->belongsTo(Pqrs::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getTypeDisplayAttribute(): string
    {
        return match ($this->type) {
            'evidence' => 'Evidencia',
            'document' => 'Documento',
            'photo' => 'Fotografía',
            default => ucfirst($this->type),
        };
    }
}
