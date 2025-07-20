<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SecuritySettings extends Settings
{
    // Security Headers
    public array $headers_csp_default_src = ["'self'"];

    public array $headers_csp_script_src = ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://cdn.jsdelivr.net'];

    public array $headers_csp_style_src = ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'];

    public array $headers_csp_font_src = ["'self'", 'https://fonts.gstatic.com'];

    public array $headers_csp_img_src = ["'self'", 'data:', 'https:'];

    public array $headers_csp_connect_src = ["'self'"];

    public array $headers_csp_media_src = ["'self'"];

    public array $headers_csp_object_src = ["'none'"];

    public array $headers_csp_frame_src = ["'none'"];

    public array $headers_csp_base_uri = ["'self'"];

    public array $headers_csp_form_action = ["'self'"];

    public array $headers_csp_frame_ancestors = ["'none'"];

    public bool $headers_csp_upgrade_insecure_requests = true;

    public string $headers_x_frame_options = 'DENY';

    public string $headers_x_content_type_options = 'nosniff';

    public string $headers_x_xss_protection = '1; mode=block';

    public string $headers_referrer_policy = 'strict-origin-when-cross-origin';

    public string $headers_permissions_policy = 'camera=(), microphone=(), geolocation=(), payment=()';

    public string $headers_strict_transport_security = 'max-age=31536000; includeSubDomains; preload';

    // Rate Limiting
    public int $rate_limits_default_attempts = 100;

    public int $rate_limits_default_decay = 60;

    public int $rate_limits_api_attempts = 60;

    public int $rate_limits_api_decay = 60;

    public int $rate_limits_auth_attempts = 5;

    public int $rate_limits_auth_decay = 60;

    public int $rate_limits_upload_attempts = 10;

    public int $rate_limits_upload_decay = 60;

    public int $rate_limits_search_attempts = 30;

    public int $rate_limits_search_decay = 60;

    public int $rate_limits_strict_attempts = 10;

    public int $rate_limits_strict_decay = 60;

    // Input Sanitization
    public bool $sanitization_enabled = true;

    public string $sanitization_allowed_tags = '<p><br><strong><em><ul><ol><li>';

    public bool $sanitization_log_threats = true;

    public bool $sanitization_block_suspicious_input = false;

    // Audit Logging
    public bool $audit_enabled = true;

    public bool $audit_log_requests = true;

    public bool $audit_log_responses = false;

    public array $audit_exclude_routes = ['assets/*', 'build/*', 'storage/*', 'health'];

    public array $audit_security_routes = ['login', 'register', 'password', 'users', 'roles', 'permissions'];

    public array $audit_sensitive_fields = ['password', 'password_confirmation', 'token', 'api_token', 'remember_token', 'secret', 'private_key', 'credit_card', 'ssn'];

    // Password Policy
    public int $password_min_length = 8;

    public bool $password_require_uppercase = true;

    public bool $password_require_lowercase = true;

    public bool $password_require_numbers = true;

    public bool $password_require_symbols = true;

    public bool $password_prevent_common_passwords = true;

    public bool $password_prevent_personal_info = true;

    public int $password_history_limit = 5;

    public int $password_max_age_days = 90;

    // Session Security
    public bool $session_secure_cookie = true;

    public string $session_same_site = 'strict';

    public int $session_timeout_warning = 5;

    public int $session_absolute_timeout = 480;

    public bool $session_regenerate_on_auth = true;

    public bool $session_invalidate_on_password_change = true;

    // File Upload Security
    public int $uploads_max_file_size = 10485760;

    public array $uploads_allowed_mime_types = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'text/plain',
        'text/csv',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public array $uploads_disallowed_extensions = [
        'php', 'js', 'exe', 'bat', 'cmd', 'sh', 'ps1', 'vbs', 'jar', 'com', 'scr', 'msi', 'dll',
    ];

    public bool $uploads_scan_for_malware = true;

    public bool $uploads_quarantine_suspicious_files = true;

    // API Security
    public bool $api_require_https = true;

    public bool $api_cors_enabled = true;

    public array $api_cors_origins = [
        'https://localhost:8000',
        'https://127.0.0.1:8000',
    ];

    public array $api_cors_methods = ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'];

    public array $api_cors_headers = ['Content-Type', 'Authorization', 'X-Requested-With'];

    public int $api_max_request_size = 10485760;

    // Two-Factor Authentication
    public bool $two_factor_enabled = false;

    public array $two_factor_required_for_roles = ['superadmin', 'admin'];

    public int $two_factor_backup_codes_count = 8;

    public int $two_factor_window = 1;

    public int $two_factor_qr_code_size = 200;

    // Encryption
    public array $encryption_encrypt_database_fields = ['users.email', 'users.phone'];

    public bool $encryption_encrypt_logs = false;

    public bool $encryption_encrypt_backups = true;

    public static function group(): string
    {
        return 'security';
    }

    private function parseCSPDirective(string $directive): array
    {
        return explode(' ', trim($directive));
    }

    public function getCSPDirective(string $directive): string
    {
        $propertyName = 'headers_csp_'.str_replace('-', '_', $directive);
        if (property_exists($this, $propertyName)) {
            return implode(' ', $this->$propertyName);
        }

        return '';
    }

    public function toSecurityConfig(): array
    {
        return [
            'headers' => [
                'csp' => [
                    'default-src' => $this->getCSPDirective('default-src'),
                    'script-src' => $this->getCSPDirective('script-src'),
                    'style-src' => $this->getCSPDirective('style-src'),
                    'font-src' => $this->getCSPDirective('font-src'),
                    'img-src' => $this->getCSPDirective('img-src'),
                    'connect-src' => $this->getCSPDirective('connect-src'),
                    'media-src' => $this->getCSPDirective('media-src'),
                    'object-src' => $this->getCSPDirective('object-src'),
                    'frame-src' => $this->getCSPDirective('frame-src'),
                    'base-uri' => $this->getCSPDirective('base-uri'),
                    'form-action' => $this->getCSPDirective('form-action'),
                    'frame-ancestors' => $this->getCSPDirective('frame-ancestors'),
                    'upgrade-insecure-requests' => $this->headers_csp_upgrade_insecure_requests,
                ],
                'x-frame-options' => $this->headers_x_frame_options,
                'x-content-type-options' => $this->headers_x_content_type_options,
                'x-xss-protection' => $this->headers_x_xss_protection,
                'referrer-policy' => $this->headers_referrer_policy,
                'permissions-policy' => $this->headers_permissions_policy,
                'strict-transport-security' => $this->headers_strict_transport_security,
            ],
            'rate_limits' => [
                'default' => [
                    'attempts' => $this->rate_limits_default_attempts,
                    'decay' => $this->rate_limits_default_decay,
                ],
                'api' => [
                    'attempts' => $this->rate_limits_api_attempts,
                    'decay' => $this->rate_limits_api_decay,
                ],
                'auth' => [
                    'attempts' => $this->rate_limits_auth_attempts,
                    'decay' => $this->rate_limits_auth_decay,
                ],
                'upload' => [
                    'attempts' => $this->rate_limits_upload_attempts,
                    'decay' => $this->rate_limits_upload_decay,
                ],
                'search' => [
                    'attempts' => $this->rate_limits_search_attempts,
                    'decay' => $this->rate_limits_search_decay,
                ],
                'strict' => [
                    'attempts' => $this->rate_limits_strict_attempts,
                    'decay' => $this->rate_limits_strict_decay,
                ],
            ],
            'sanitization' => [
                'enabled' => $this->sanitization_enabled,
                'allowed_tags' => $this->sanitization_allowed_tags,
                'log_threats' => $this->sanitization_log_threats,
                'block_suspicious_input' => $this->sanitization_block_suspicious_input,
            ],
            'audit' => [
                'enabled' => $this->audit_enabled,
                'log_requests' => $this->audit_log_requests,
                'log_responses' => $this->audit_log_responses,
                'exclude_routes' => $this->audit_exclude_routes,
                'security_routes' => $this->audit_security_routes,
                'sensitive_fields' => $this->audit_sensitive_fields,
            ],
            'password' => [
                'min_length' => $this->password_min_length,
                'require_uppercase' => $this->password_require_uppercase,
                'require_lowercase' => $this->password_require_lowercase,
                'require_numbers' => $this->password_require_numbers,
                'require_symbols' => $this->password_require_symbols,
                'prevent_common_passwords' => $this->password_prevent_common_passwords,
                'prevent_personal_info' => $this->password_prevent_personal_info,
                'history_limit' => $this->password_history_limit,
                'max_age_days' => $this->password_max_age_days,
            ],
            'session' => [
                'secure_cookie' => $this->session_secure_cookie,
                'same_site' => $this->session_same_site,
                'timeout_warning' => $this->session_timeout_warning,
                'absolute_timeout' => $this->session_absolute_timeout,
                'regenerate_on_auth' => $this->session_regenerate_on_auth,
                'invalidate_on_password_change' => $this->session_invalidate_on_password_change,
            ],
            'uploads' => [
                'max_file_size' => $this->uploads_max_file_size,
                'allowed_mime_types' => $this->uploads_allowed_mime_types,
                'disallowed_extensions' => $this->uploads_disallowed_extensions,
                'scan_for_malware' => $this->uploads_scan_for_malware,
                'quarantine_suspicious_files' => $this->uploads_quarantine_suspicious_files,
            ],
            'api' => [
                'require_https' => $this->api_require_https,
                'cors_enabled' => $this->api_cors_enabled,
                'cors_origins' => $this->api_cors_origins,
                'cors_methods' => $this->api_cors_methods,
                'cors_headers' => $this->api_cors_headers,
                'max_request_size' => $this->api_max_request_size,
            ],
            '2fa' => [
                'enabled' => $this->two_factor_enabled,
                'required_for_roles' => $this->two_factor_required_for_roles,
                'backup_codes_count' => $this->two_factor_backup_codes_count,
                'window' => $this->two_factor_window,
                'qr_code_size' => $this->two_factor_qr_code_size,
            ],
            'encryption' => [
                'encrypt_database_fields' => $this->encryption_encrypt_database_fields,
                'encrypt_logs' => $this->encryption_encrypt_logs,
                'encrypt_backups' => $this->encryption_encrypt_backups,
            ],
        ];
    }
}
