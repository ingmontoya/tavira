<?php

use App\Http\Middleware\AuditLogMiddleware;
use App\Http\Middleware\CheckProviderPlan;
use App\Http\Middleware\EnsureCanCreateMultipleConjuntos;
use App\Http\Middleware\EnsureConjuntoConfigured;
use App\Http\Middleware\EnsureUserIsCompany;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\InputSanitizationMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\RedirectIfSubscribed;
use App\Http\Middleware\RequiresFeature;
use App\Http\Middleware\RequiresSubscription;
use App\Http\Middleware\SecurityHeadersMiddleware;
use App\Http\Middleware\SharePermissions;
use App\Http\Middleware\ShareTenantFeatures;
use App\Http\Middleware\TrustProxies;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\HandleCors;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withSchedule(function ($schedule) {
        // Generate monthly invoices on the 1st of each month at 00:01
        $schedule->command('invoices:generate-monthly')->monthlyOn(1, '00:01');

        // Process late fees on the 1st of each month at 09:00
        $schedule->command('invoices:process-late-fees')->monthlyOn(1, '09:00');

        // Update apartment payment statuses daily at 03:00 (runs for all tenants)
        $schedule->command('tenants:run apartments:update-payment-status')->dailyAt('03:00');

        // Sync tenant subscription status every hour
        $schedule->command('tenants:sync-subscription-status')->hourly();

        // Also run a daily check to catch any missed updates
        $schedule->command('tenants:sync-subscription-status')->dailyAt('06:00');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(prepend: [
            TrustProxies::class, // Must be first to handle X-Forwarded-* headers
        ]);

        $middleware->web(append: [
            HandleCors::class,
            VerifyCsrfToken::class,
            // SecurityHeadersMiddleware::class, // Temporarily disabled for testing
            AuditLogMiddleware::class,
            InputSanitizationMiddleware::class,
            HandleAppearance::class,
            HandleInertiaRequests::class,
            SharePermissions::class,
            ShareTenantFeatures::class,
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
            'redirect.if.subscribed' => RedirectIfSubscribed::class,
            'requires.feature' => RequiresFeature::class,
            'check.provider.plan' => CheckProviderPlan::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        Integration::handles($exceptions);
    })->create();
