<?php

use App\Http\Middleware\AuditLogMiddleware;
use App\Http\Middleware\EnsureCanCreateMultipleConjuntos;
use App\Http\Middleware\EnsureConjuntoConfigured;
use App\Http\Middleware\EnsureUserIsCompany;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\InputSanitizationMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\RequiresSubscription;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SharePermissions;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function ($schedule) {
        $schedule->command('invoices:process-late-fees')->monthlyOn(1, '09:00');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleCors::class,
            VerifyCsrfToken::class,
            // SecurityHeadersMiddleware::class, // Temporarily disabled for testing
            AuditLogMiddleware::class,
            InputSanitizationMiddleware::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            SharePermissions::class,
            RequiresSubscription::class,
            EnsureConjuntoConfigured::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->api(append: [
            HandleCors::class,
            // InputSanitizationMiddleware::class, // API requests don't need heavy sanitization
            AuditLogMiddleware::class,
        ]);

        // Register middleware aliases
        $middleware->alias([
            'rate.limit' => RateLimitMiddleware::class,
            'company' => EnsureUserIsCompany::class,
            'multiple.conjuntos' => EnsureCanCreateMultipleConjuntos::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
