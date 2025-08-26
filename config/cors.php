<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'register', 'logout', 'maintenance-requests/*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    'allowed_origins' => env('CORS_ALLOWED_ORIGINS') ?
        explode(',', env('CORS_ALLOWED_ORIGINS')) : [
            'https://localhost:8000',
            'https://127.0.0.1:8000',
            'http://localhost:8000',
            'http://127.0.0.1:8000',
            'http://localhost:3001',
            'http://127.0.0.1:3000',
            'http://localhost:5173',
            'http://127.0.0.1:5173',
            'http://192.168.1.12:3000',
            'http://192.168.1.12:3001',
            'http://192.168.1.12:8000',
        ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'Retry-After',
    ],

    'max_age' => 86400,

    'supports_credentials' => true,

];
