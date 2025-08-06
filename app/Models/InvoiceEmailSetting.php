<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Crypt;

class InvoiceEmailSetting extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'key',
        'name',
        'description',
        'category',
        'value',
        'options',
        'type',
        'validation_rules',
        'default_value',
        'is_required',
        'is_encrypted',
        'input_type',
        'help_text',
        'placeholder',
        'sort_order',
        'environments',
        'is_visible',
        'is_editable',
        'requires_restart',
        'is_system',
        'last_modified_at',
        'last_modified_by',
    ];

    protected $casts = [
        'options' => 'array',
        'validation_rules' => 'array',
        'environments' => 'array',
        'is_required' => 'boolean',
        'is_encrypted' => 'boolean',
        'is_visible' => 'boolean',
        'is_editable' => 'boolean',
        'requires_restart' => 'boolean',
        'is_system' => 'boolean',
        'last_modified_at' => 'datetime',
    ];

    protected $appends = [
        'display_value',
        'parsed_value',
        'is_default',
        'validation_rules_array',
    ];

    // Relationships
    public function lastModifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_modified_by');
    }

    // Scopes
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeEditable(Builder $query): Builder
    {
        return $query->where('is_editable', true);
    }

    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    public function scopeSystem(Builder $query): Builder
    {
        return $query->where('is_system', true);
    }

    public function scopeUserDefined(Builder $query): Builder
    {
        return $query->where('is_system', false);
    }

    public function scopeByEnvironment(Builder $query, string $environment): Builder
    {
        return $query->where(function ($q) use ($environment) {
            $q->whereJsonContains('environments', $environment)
              ->orWhereNull('environments');
        });
    }

    public function scopeForCurrentEnvironment(Builder $query): Builder
    {
        return $query->byEnvironment(app()->environment());
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('category')
            ->orderBy('sort_order')
            ->orderBy('name');
    }

    // Accessors
    public function getDisplayValueAttribute(): mixed
    {
        if ($this->is_encrypted && $this->value) {
            return '••••••••';
        }

        if ($this->type === 'boolean') {
            return $this->parsed_value ? 'Sí' : 'No';
        }

        if ($this->type === 'select' && $this->options) {
            $option = collect($this->options)->firstWhere('value', $this->value);
            return $option['label'] ?? $this->value;
        }

        return $this->value;
    }

    public function getParsedValueAttribute(): mixed
    {
        $value = $this->getRawValue();

        return match ($this->type) {
            'integer' => (int) $value,
            'decimal' => (float) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($value, true),
            default => $value,
        };
    }

    public function getIsDefaultAttribute(): bool
    {
        return $this->value === $this->default_value || 
               ($this->value === null && $this->default_value === null);
    }

    public function getValidationRulesArrayAttribute(): array
    {
        if (!$this->validation_rules) {
            return [];
        }

        return is_array($this->validation_rules) ? $this->validation_rules : [$this->validation_rules];
    }

    // Value Management
    public function getRawValue(): mixed
    {
        if (!$this->value) {
            return $this->getDefaultValue();
        }

        if ($this->is_encrypted) {
            try {
                return Crypt::decryptString($this->value);
            } catch (\Exception $e) {
                return $this->getDefaultValue();
            }
        }

        return $this->value;
    }

    public function getDefaultValue(): mixed
    {
        $default = $this->default_value;

        return match ($this->type) {
            'integer' => (int) $default,
            'decimal' => (float) $default,
            'boolean' => filter_var($default, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($default, true),
            default => $default,
        };
    }

    public function setValue(mixed $value, ?int $userId = null): bool
    {
        if ($this->is_encrypted && $value !== null) {
            $value = Crypt::encryptString($value);
        }

        $updated = $this->update([
            'value' => $value,
            'last_modified_at' => now(),
            'last_modified_by' => $userId ?? auth()->id(),
        ]);

        if ($updated && $this->requires_restart) {
            $this->markForRestart();
        }

        return $updated;
    }

    public function resetToDefault(?int $userId = null): bool
    {
        return $this->setValue($this->default_value, $userId);
    }

    public function isValidValue(mixed $value): bool
    {
        if ($this->is_required && ($value === null || $value === '')) {
            return false;
        }

        if (!$this->validation_rules) {
            return true;
        }

        $validator = validator(['value' => $value], [
            'value' => $this->validation_rules_array,
        ]);

        return !$validator->fails();
    }

    public function validateAndSet(mixed $value, ?int $userId = null): bool
    {
        if (!$this->isValidValue($value)) {
            return false;
        }

        return $this->setValue($value, $userId);
    }

    // Category Management
    public static function getCategories(): array
    {
        return [
            'general' => 'General',
            'templates' => 'Plantillas',
            'smtp' => 'Configuración SMTP',
            'notifications' => 'Notificaciones',
            'security' => 'Seguridad',
            'cost' => 'Costos',
            'delivery' => 'Entrega',
        ];
    }

    public static function getByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return self::byCategory($category)
            ->visible()
            ->forCurrentEnvironment()
            ->ordered()
            ->get();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return $default;
        }

        return $setting->parsed_value ?? $default;
    }

    public static function set(string $key, mixed $value, ?int $userId = null): bool
    {
        $setting = self::where('key', $key)->first();

        if (!$setting) {
            return false;
        }

        return $setting->setValue($value, $userId);
    }

    // System Settings Helpers
    private function markForRestart(): void
    {
        // Could implement a mechanism to mark the system for restart
        // or notify administrators about required restart
        logger()->info("Setting {$this->key} changed - system restart may be required");
    }

    // Boot method for automatic system setting creation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($setting) {
            $setting->last_modified_at = now();
            $setting->last_modified_by = auth()->id();
        });

        static::updating(function ($setting) {
            if ($setting->isDirty(['value'])) {
                $setting->last_modified_at = now();
                $setting->last_modified_by = auth()->id();
            }
        });
    }

    // Factory method for creating system settings
    public static function createSystemSetting(array $attributes): self
    {
        $attributes['is_system'] = true;
        $attributes['is_visible'] = $attributes['is_visible'] ?? true;
        $attributes['is_editable'] = $attributes['is_editable'] ?? true;

        return self::create($attributes);
    }
}