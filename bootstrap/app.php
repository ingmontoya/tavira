<?php

use App\Http\Middleware\AuditLogMiddleware;
use App\Http\Middleware\EnsureCanCreateMultipleConjuntos;
use App\Http\Middleware\EnsureUserIsCompany;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\InputSanitizationMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SharePermissions;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\URL;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function ($schedule) {
        $schedule->command('invoices:process-late-fees')->monthlyOn(1, '09:00');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        // Force HTTPS in production
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Trust specific proxy IPs - Cloudflare IP ranges
        $middleware->trustProxies(
            at: env('TRUSTED_PROXIES') ? 
                explode(',', env('TRUSTED_PROXIES')) : 
                [
                    // Cloudflare IPv4 ranges (principales)
                    '103.21.244.0/22',
                    '103.22.200.0/22', 
                    '103.31.4.0/22',
                    '104.16.0.0/13',
                    '104.24.0.0/14',
                    '108.162.192.0/18',
                    '131.0.72.0/22',
                    '141.101.64.0/18',
                    '162.158.0.0/15',
                    '172.64.0.0/13',
                    '173.245.48.0/20',
                    '188.114.96.0/20',
                    '190.93.240.0/20',
                    '197.234.240.0/22',
                    '198.41.128.0/17',
                    // Laravel Cloud internal network
                    '10.0.0.0/8',
                    '172.16.0.0/12',
                    '192.168.0.0/16',
                ],
            headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR |
                    \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST |
                    \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT |
                    \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO
        );

        $middleware->web(append: [
            // SecurityHeadersMiddleware::class, // Temporarily disabled for testing
            AuditLogMiddleware::class,
            InputSanitizationMiddleware::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            SharePermissions::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'rate.limit' => RateLimitMiddleware::class,
            'company' => EnsureUserIsCompany::class,
            'multiple.conjuntos' => EnsureCanCreateMultipleConjuntos::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
