<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Settings\SecuritySettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class SecuritySettingsController extends Controller
{
    protected SecuritySettings $securitySettings;

    public function __construct(SecuritySettings $securitySettings)
    {
        $this->securitySettings = $securitySettings;
    }

    public function index()
    {
        return Inertia::render('settings/Security', [
            'settings' => $this->securitySettings->toArray(),
            'securityLevels' => $this->getSecurityLevels(),
        ]);
    }

    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), $this->getValidationRules());

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $validatedData = $validator->validated();

        // Update security settings
        foreach ($validatedData as $key => $value) {
            if (property_exists($this->securitySettings, $key)) {
                $this->securitySettings->$key = $value;
            }
        }

        $this->securitySettings->save();

        Log::info('Security settings updated', [
            'user_id' => auth()->user()->id,
            'updated_settings' => array_keys($validatedData),
            'ip' => $request->ip(),
        ]);

        return back()->with('success', 'Configuración de seguridad actualizada correctamente');
    }

    public function applySecurityLevel(Request $request)
    {
        $request->validate([
            'level' => 'required|in:basic,medium,high,maximum',
        ]);

        $level = $request->input('level');
        $this->applySecurityLevelConfiguration($level);

        Log::info('Security level applied', [
            'user_id' => auth()->user()->id,
            'security_level' => $level,
            'ip' => $request->ip(),
        ]);

        return back()->with('success', "Nivel de seguridad '{$level}' aplicado correctamente");
    }

    public function resetToDefaults()
    {
        // Reset all settings to default values by recreating the settings
        $config = config('security');

        // Manually reset to defaults based on config
        $this->securitySettings->headers_csp_default_src = $this->parseCSPDirective($config['headers']['csp']['default-src'] ?? "'self'");
        $this->securitySettings->headers_csp_script_src = $this->parseCSPDirective($config['headers']['csp']['script-src'] ?? "'self'");
        $this->securitySettings->rate_limits_auth_attempts = $config['rate_limits']['auth']['attempts'] ?? 5;
        $this->securitySettings->password_min_length = $config['password']['min_length'] ?? 8;
        $this->securitySettings->two_factor_enabled = $config['2fa']['enabled'] ?? false;

        $this->securitySettings->save();

        Log::info('Security settings reset to defaults', [
            'user_id' => auth()->user()->id,
            'ip' => request()->ip(),
        ]);

        return back()->with('success', 'Configuración de seguridad restaurada a valores predeterminados');
    }

    private function getSecurityLevels(): array
    {
        return [
            'basic' => [
                'name' => 'Básico',
                'description' => 'Configuración básica de seguridad para entornos de desarrollo',
                'settings' => [
                    'rate_limits_default_attempts' => 200,
                    'rate_limits_auth_attempts' => 10,
                    'password_min_length' => 6,
                    'password_require_uppercase' => false,
                    'password_require_symbols' => false,
                    'two_factor_enabled' => false,
                    'uploads_scan_for_malware' => false,
                    'audit_log_requests' => false,
                    'sanitization_block_suspicious_input' => false,
                ]
            ],
            'medium' => [
                'name' => 'Medio',
                'description' => 'Configuración equilibrada para entornos de prueba',
                'settings' => [
                    'rate_limits_default_attempts' => 100,
                    'rate_limits_auth_attempts' => 5,
                    'password_min_length' => 8,
                    'password_require_uppercase' => true,
                    'password_require_symbols' => true,
                    'two_factor_enabled' => false,
                    'uploads_scan_for_malware' => true,
                    'audit_log_requests' => true,
                    'sanitization_block_suspicious_input' => false,
                ]
            ],
            'high' => [
                'name' => 'Alto',
                'description' => 'Configuración robusta para entornos de producción',
                'settings' => [
                    'rate_limits_default_attempts' => 60,
                    'rate_limits_auth_attempts' => 3,
                    'password_min_length' => 10,
                    'password_require_uppercase' => true,
                    'password_require_symbols' => true,
                    'password_prevent_common_passwords' => true,
                    'two_factor_enabled' => true,
                    'uploads_scan_for_malware' => true,
                    'audit_log_requests' => true,
                    'sanitization_block_suspicious_input' => true,
                ]
            ],
            'maximum' => [
                'name' => 'Máximo',
                'description' => 'Configuración de máxima seguridad para datos sensibles',
                'settings' => [
                    'rate_limits_default_attempts' => 30,
                    'rate_limits_auth_attempts' => 2,
                    'password_min_length' => 12,
                    'password_require_uppercase' => true,
                    'password_require_symbols' => true,
                    'password_prevent_common_passwords' => true,
                    'password_prevent_personal_info' => true,
                    'password_history_limit' => 10,
                    'two_factor_enabled' => true,
                    'uploads_scan_for_malware' => true,
                    'uploads_quarantine_suspicious_files' => true,
                    'audit_log_requests' => true,
                    'audit_log_responses' => true,
                    'sanitization_block_suspicious_input' => true,
                    'session_absolute_timeout' => 240, // 4 hours
                ]
            ],
        ];
    }

    private function applySecurityLevelConfiguration(string $level): void
    {
        $levels = $this->getSecurityLevels();

        if (!isset($levels[$level])) {
            throw new \InvalidArgumentException("Invalid security level: {$level}");
        }

        $settings = $levels[$level]['settings'];

        foreach ($settings as $key => $value) {
            if (property_exists($this->securitySettings, $key)) {
                $this->securitySettings->$key = $value;
            }
        }

        $this->securitySettings->save();
    }


    private function parseCSPDirective(string $directive): array
    {
        return explode(' ', trim($directive));
    }

    private function getValidationRules(): array
    {
        return [
            // Security Headers
            'headers_csp_default_src' => 'nullable|array',
            'headers_csp_script_src' => 'nullable|array',
            'headers_csp_style_src' => 'nullable|array',
            'headers_csp_font_src' => 'nullable|array',
            'headers_csp_img_src' => 'nullable|array',
            'headers_csp_connect_src' => 'nullable|array',
            'headers_csp_media_src' => 'nullable|array',
            'headers_csp_object_src' => 'nullable|array',
            'headers_csp_frame_src' => 'nullable|array',
            'headers_csp_base_uri' => 'nullable|array',
            'headers_csp_form_action' => 'nullable|array',
            'headers_csp_frame_ancestors' => 'nullable|array',
            'headers_csp_upgrade_insecure_requests' => 'boolean',
            'headers_x_frame_options' => 'string|in:DENY,SAMEORIGIN',
            'headers_x_content_type_options' => 'string|in:nosniff',
            'headers_x_xss_protection' => 'string',
            'headers_referrer_policy' => 'string|in:no-referrer,no-referrer-when-downgrade,origin,origin-when-cross-origin,same-origin,strict-origin,strict-origin-when-cross-origin,unsafe-url',
            'headers_permissions_policy' => 'string',
            'headers_strict_transport_security' => 'string',

            // Rate Limiting
            'rate_limits_default_attempts' => 'integer|min:1|max:1000',
            'rate_limits_default_decay' => 'integer|min:1|max:3600',
            'rate_limits_api_attempts' => 'integer|min:1|max:1000',
            'rate_limits_api_decay' => 'integer|min:1|max:3600',
            'rate_limits_auth_attempts' => 'integer|min:1|max:50',
            'rate_limits_auth_decay' => 'integer|min:1|max:3600',
            'rate_limits_upload_attempts' => 'integer|min:1|max:100',
            'rate_limits_upload_decay' => 'integer|min:1|max:3600',
            'rate_limits_search_attempts' => 'integer|min:1|max:500',
            'rate_limits_search_decay' => 'integer|min:1|max:3600',
            'rate_limits_strict_attempts' => 'integer|min:1|max:50',
            'rate_limits_strict_decay' => 'integer|min:1|max:3600',

            // Input Sanitization
            'sanitization_enabled' => 'boolean',
            'sanitization_allowed_tags' => 'string',
            'sanitization_log_threats' => 'boolean',
            'sanitization_block_suspicious_input' => 'boolean',

            // Audit Logging
            'audit_enabled' => 'boolean',
            'audit_log_requests' => 'boolean',
            'audit_log_responses' => 'boolean',
            'audit_exclude_routes' => 'array',
            'audit_security_routes' => 'array',
            'audit_sensitive_fields' => 'array',

            // Password Policy
            'password_min_length' => 'integer|min:4|max:128',
            'password_require_uppercase' => 'boolean',
            'password_require_lowercase' => 'boolean',
            'password_require_numbers' => 'boolean',
            'password_require_symbols' => 'boolean',
            'password_prevent_common_passwords' => 'boolean',
            'password_prevent_personal_info' => 'boolean',
            'password_history_limit' => 'integer|min:0|max:50',
            'password_max_age_days' => 'integer|min:1|max:365',

            // Session Security
            'session_secure_cookie' => 'boolean',
            'session_same_site' => 'string|in:strict,lax,none',
            'session_timeout_warning' => 'integer|min:1|max:60',
            'session_absolute_timeout' => 'integer|min:5|max:1440',
            'session_regenerate_on_auth' => 'boolean',
            'session_invalidate_on_password_change' => 'boolean',

            // File Upload Security
            'uploads_max_file_size' => 'integer|min:1024|max:104857600', // 1KB to 100MB
            'uploads_allowed_mime_types' => 'array',
            'uploads_disallowed_extensions' => 'array',
            'uploads_scan_for_malware' => 'boolean',
            'uploads_quarantine_suspicious_files' => 'boolean',

            // API Security
            'api_require_https' => 'boolean',
            'api_cors_enabled' => 'boolean',
            'api_cors_origins' => 'array',
            'api_cors_methods' => 'array',
            'api_cors_headers' => 'array',
            'api_max_request_size' => 'integer|min:1024|max:104857600',

            // Two-Factor Authentication
            'two_factor_enabled' => 'boolean',
            'two_factor_required_for_roles' => 'array',
            'two_factor_backup_codes_count' => 'integer|min:1|max:20',
            'two_factor_window' => 'integer|min:0|max:10',
            'two_factor_qr_code_size' => 'integer|min:100|max:500',

            // Encryption
            'encryption_encrypt_database_fields' => 'array',
            'encryption_encrypt_logs' => 'boolean',
            'encryption_encrypt_backups' => 'boolean',
        ];
    }

       /**
     * Fuerza el guardado de todos los valores actuales de SecuritySettings en la base de datos.
     * Útil para inicializar o reparar el registro de settings si faltan propiedades.
     */
    public function forceSaveAllSecuritySettings()
    {
        foreach (get_object_vars($this->securitySettings) as $key => $value) {
            $this->securitySettings->$key = $value;
        }
        $this->securitySettings->save();
        return back()->with('success', 'Todos los valores de seguridad han sido guardados forzadamente.');
    }
}
