<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmailTemplate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'type',
        'subject',
        'body',
        'variables',
        'design_config',
        'is_active',
        'is_default',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'variables' => 'array',
        'design_config' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    // Available template types
    public const TYPES = [
        'invoice' => 'Factura',
        'payment_receipt' => 'Recibo de Pago',
        'payment_reminder' => 'Recordatorio de Pago',
        'welcome' => 'Bienvenida',
        'announcement' => 'Comunicado',
        'custom' => 'Personalizado',
    ];

    // Default variables for each type
    public const DEFAULT_VARIABLES = [
        'invoice' => [
            '{{invoice_number}}',
            '{{apartment_number}}',
            '{{apartment_address}}',
            '{{billing_period}}',
            '{{due_date}}',
            '{{total_amount}}',
            '{{balance_due}}',
            '{{conjunto_name}}',
            '{{billing_date}}',
        ],
        'payment_receipt' => [
            '{{payment_amount}}',
            '{{payment_date}}',
            '{{payment_method}}',
            '{{receipt_number}}',
            '{{apartment_number}}',
            '{{apartment_address}}',
            '{{conjunto_name}}',
        ],
        'payment_reminder' => [
            '{{apartment_number}}',
            '{{apartment_address}}',
            '{{overdue_amount}}',
            '{{days_overdue}}',
            '{{conjunto_name}}',
            '{{due_date}}',
        ],
        'welcome' => [
            '{{user_name}}',
            '{{apartment_number}}',
            '{{conjunto_name}}',
            '{{login_url}}',
        ],
        'announcement' => [
            '{{announcement_title}}',
            '{{announcement_content}}',
            '{{announcement_date}}',
            '{{conjunto_name}}',
            '{{author_name}}',
        ],
        'custom' => [],
    ];

    // Default design configurations
    public const DEFAULT_DESIGN_CONFIG = [
        'primary_color' => '#3b82f6',
        'secondary_color' => '#1e40af',
        'background_color' => '#f8fafc',
        'text_color' => '#1e293b',
        'font_family' => 'Arial, sans-serif',
        'header_style' => 'modern',
        'footer_style' => 'simple',
        'button_style' => 'rounded',
        'layout' => 'centered',
        'max_width' => '600px',
        'show_logo' => true,
        'show_contact_info' => true,
    ];

    // Relationships
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeDefault(Builder $query): Builder
    {
        return $query->where('is_default', true);
    }

    // Methods
    public function getAvailableVariables(): array
    {
        return self::DEFAULT_VARIABLES[$this->type] ?? [];
    }

    public function getDesignConfig(): array
    {
        return array_merge(self::DEFAULT_DESIGN_CONFIG, $this->design_config ?? []);
    }

    public function processTemplate(array $data): array
    {
        $subject = $this->subject;
        $body = $this->body;

        foreach ($data as $key => $value) {
            $placeholder = "{{{$key}}}";
            $subject = str_replace($placeholder, $value, $subject);
            $body = str_replace($placeholder, $value, $body);
        }

        return [
            'subject' => $subject,
            'body' => $body,
            'design_config' => $this->getDesignConfig(),
        ];
    }

    public function setAsDefault(): bool
    {
        // Remove default from other templates of the same type
        self::where('type', $this->type)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        return $this->update(['is_default' => true]);
    }

    public static function getDefaultForType(string $type): ?self
    {
        return self::active()
            ->byType($type)
            ->default()
            ->first();
    }

    public static function getActiveByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return self::active()
            ->byType($type)
            ->orderBy('is_default', 'desc')
            ->orderBy('name')
            ->get();
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($template) {
            $template->created_by = auth()->id();
            $template->updated_by = auth()->id();

            // Set default variables if not provided
            if (empty($template->variables)) {
                $template->variables = self::DEFAULT_VARIABLES[$template->type] ?? [];
            }

            // Set default design config if not provided
            if (empty($template->design_config)) {
                $template->design_config = self::DEFAULT_DESIGN_CONFIG;
            }
        });

        static::updating(function ($template) {
            $template->updated_by = auth()->id();
        });
    }
}
