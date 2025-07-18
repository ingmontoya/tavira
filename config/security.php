<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Security Headers Configuration
    |--------------------------------------------------------------------------
    */
    'headers' => [
        'csp' => [
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
            'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com",
            'font-src' => "'self' https://fonts.gstatic.com",
            'img-src' => "'self' data: https:",
            'connect-src' => "'self'",
            'media-src' => "'self'",
            'object-src' => "'none'",
            'frame-src' => "'none'",
            'base-uri' => "'self'",
            'form-action' => "'self'",
            'frame-ancestors' => "'none'",
            'upgrade-insecure-requests' => true,
        ],
        'x-frame-options' => 'DENY',
        'x-content-type-options' => 'nosniff',
        'x-xss-protection' => '1; mode=block',
        'referrer-policy' => 'strict-origin-when-cross-origin',
        'permissions-policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        'strict-transport-security' => 'max-age=31536000; includeSubDomains; preload',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    */
    'rate_limits' => [
        'default' => [
            'attempts' => 100,
            'decay' => 60,
        ],
        'api' => [
            'attempts' => 60,
            'decay' => 60,
        ],
        'auth' => [
            'attempts' => 5,
            'decay' => 60,
        ],
        'upload' => [
            'attempts' => 10,
            'decay' => 60,
        ],
        'search' => [
            'attempts' => 30,
            'decay' => 60,
        ],
        'strict' => [
            'attempts' => 10,
            'decay' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Input Sanitization Configuration
    |--------------------------------------------------------------------------
    */
    'sanitization' => [
        'enabled' => true,
        'allowed_tags' => '<p><br><strong><em><ul><ol><li>',
        'log_threats' => true,
        'block_suspicious_input' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Logging Configuration
    |--------------------------------------------------------------------------
    */
    'audit' => [
        'enabled' => true,
        'log_requests' => true,
        'log_responses' => false,
        'exclude_routes' => [
            'assets/*',
            'build/*',
            'storage/*',
            'health',
        ],
        'security_routes' => [
            'login',
            'register',
            'password',
            'users',
            'roles',
            'permissions',
        ],
        'sensitive_fields' => [
            'password',
            'password_confirmation',
            'token',
            'api_token',
            'remember_token',
            'secret',
            'private_key',
            'credit_card',
            'ssn',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password Policy Configuration
    |--------------------------------------------------------------------------
    */
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'prevent_common_passwords' => true,
        'prevent_personal_info' => true,
        'history_limit' => 5,
        'max_age_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Security Configuration
    |--------------------------------------------------------------------------
    */
    'session' => [
        'secure_cookie' => env('SESSION_SECURE_COOKIE', true),
        'same_site' => 'strict',
        'timeout_warning' => 5, // minutes before session expires
        'absolute_timeout' => 480, // minutes (8 hours)
        'regenerate_on_auth' => true,
        'invalidate_on_password_change' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Upload Security Configuration
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'max_file_size' => 10485760, // 10MB
        'allowed_mime_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'text/plain',
            'text/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ],
        'disallowed_extensions' => [
            'php',
            'js',
            'exe',
            'bat',
            'cmd',
            'sh',
            'ps1',
            'vbs',
            'jar',
            'com',
            'scr',
            'msi',
            'dll',
        ],
        'scan_for_malware' => true,
        'quarantine_suspicious_files' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Security Configuration
    |--------------------------------------------------------------------------
    */
    'api' => [
        'require_https' => env('API_REQUIRE_HTTPS', true),
        'cors_enabled' => true,
        'cors_origins' => [
            'https://localhost:8000',
            'https://127.0.0.1:8000',
        ],
        'cors_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'cors_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        'max_request_size' => 10485760, // 10MB
    ],

    /*
    |--------------------------------------------------------------------------
    | Two-Factor Authentication Configuration
    |--------------------------------------------------------------------------
    */
    '2fa' => [
        'enabled' => false,
        'required_for_roles' => ['superadmin', 'admin'],
        'backup_codes_count' => 8,
        'window' => 1, // TOTP window tolerance
        'qr_code_size' => 200,
    ],

    /*
    |--------------------------------------------------------------------------
    | Encryption Configuration
    |--------------------------------------------------------------------------
    */
    'encryption' => [
        'encrypt_database_fields' => [
            'users.email',
            'users.phone',
        ],
        'encrypt_logs' => false,
        'encrypt_backups' => true,
    ],
];