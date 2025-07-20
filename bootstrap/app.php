<?php

use App\Http\Middleware\AuditLogMiddleware;
use App\Http\Middleware\EnsureCanCreateMultipleConjuntos;
use App\Http\Middleware\EnsureUserIsCompany;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\InputSanitizationMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            // SecurityHeadersMiddleware::class, // Temporarily disabled for testing
            AuditLogMiddleware::class,
            InputSanitizationMiddleware::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
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
