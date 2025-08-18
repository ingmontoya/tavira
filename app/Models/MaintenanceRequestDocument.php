<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MaintenanceRequestDocument extends Model
{
    protected $fillable = [
        'maintenance_request_id',
        'uploaded_by_user_id',
        'name',
        'file_path',
        'file_type',
        'file_size',
        'stage',
        'document_type',
        'description',
        'metadata',
        'is_evidence',
        'is_required_approval',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_evidence' => 'boolean',
        'is_required_approval' => 'boolean',
        'file_size' => 'integer',
    ];

    public const STAGE_INITIAL_REQUEST = 'initial_request';

    public const STAGE_EVALUATION = 'evaluation';

    public const STAGE_BUDGETING = 'budgeting';

    public const STAGE_APPROVAL = 'approval';

    public const STAGE_EXECUTION = 'execution';

    public const STAGE_COMPLETION = 'completion';

    public const STAGE_EVIDENCE = 'evidence';

    public const STAGE_WARRANTY = 'warranty';

    public const TYPE_PHOTO = 'photo';

    public const TYPE_QUOTE = 'quote';

    public const TYPE_INVOICE = 'invoice';

    public const TYPE_RECEIPT = 'receipt';

    public const TYPE_REPORT = 'report';

    public const TYPE_SPECIFICATION = 'specification';

    public const TYPE_PERMIT = 'permit';

    public const TYPE_WARRANTY = 'warranty';

    public const TYPE_OTHER = 'other';

    public static function getStageLabels(): array
    {
        return [
            self::STAGE_INITIAL_REQUEST => 'Solicitud Inicial',
            self::STAGE_EVALUATION => 'Evaluación',
            self::STAGE_BUDGETING => 'Presupuestación',
            self::STAGE_APPROVAL => 'Aprobación',
            self::STAGE_EXECUTION => 'Ejecución',
            self::STAGE_COMPLETION => 'Finalización',
            self::STAGE_EVIDENCE => 'Evidencia',
            self::STAGE_WARRANTY => 'Garantía',
        ];
    }

    public static function getDocumentTypeLabels(): array
    {
        return [
            self::TYPE_PHOTO => 'Fotografía',
            self::TYPE_QUOTE => 'Cotización',
            self::TYPE_INVOICE => 'Factura',
            self::TYPE_RECEIPT => 'Recibo',
            self::TYPE_REPORT => 'Reporte',
            self::TYPE_SPECIFICATION => 'Especificación',
            self::TYPE_PERMIT => 'Permiso',
            self::TYPE_WARRANTY => 'Garantía',
            self::TYPE_OTHER => 'Otro',
        ];
    }

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function getUrlAttribute(): string
    {
        return Storage::url($this->file_path);
    }

    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    public function getStageLabel(): string
    {
        return self::getStageLabels()[$this->stage] ?? $this->stage;
    }

    public function getDocumentTypeLabel(): string
    {
        return self::getDocumentTypeLabels()[$this->document_type] ?? $this->document_type;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->file_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->file_type === 'application/pdf';
    }

    public function canBeViewed(): bool
    {
        return $this->isImage() || $this->isPdf();
    }
}
