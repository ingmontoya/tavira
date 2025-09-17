<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Security Management Routes
|--------------------------------------------------------------------------
|
| Here are the routes for security management including alerts, access logs,
| visitor management, and panic alerts monitoring.
|
*/

Route::prefix('security')->name('security.')->group(function () {

    // Security Alerts Dashboard
    Route::get('/alerts', function () {
        return Inertia::render('security/alerts');
    })->name('alerts.index')
      ->middleware('can:view_security_alerts,view_panic_alerts');

    // Panic Alerts Management (for security personnel)
    Route::prefix('panic-alerts')->name('panic-alerts.')->group(function () {

        // Dashboard for viewing active panic alerts
        Route::get('/', function () {
            return Inertia::render('security/panic-alerts/dashboard');
        })->name('dashboard')
          ->middleware('can:view_panic_alerts,manage_security_alerts');

        // Individual panic alert details
        Route::get('/{panicAlert}', function (\App\Models\PanicAlert $panicAlert) {
            return Inertia::render('security/panic-alerts/show', [
                'alert' => $panicAlert->load(['user', 'apartment']),
            ]);
        })->name('show')
          ->middleware('can:view_panic_alerts,manage_security_alerts');
    });

    // Access Logs (for security personnel and administrators)
    Route::get('/access-logs', function () {
        return Inertia::render('security/access-logs');
    })->name('access-logs.index')
      ->middleware('can:view_access_logs');

    // Visitor Management (enhanced for security personnel)
    Route::prefix('visitors')->name('visitors.')->group(function () {

        // Security dashboard for visitor management
        Route::get('/dashboard', function () {
            return Inertia::render('security/visitors/dashboard');
        })->name('dashboard')
          ->middleware('can:manage_visitors');

        // QR Code scanner for visitor check-in
        Route::get('/scanner', function () {
            return Inertia::render('security/visitors/scanner');
        })->name('scanner')
          ->middleware('can:manage_visitors');

        // Recent entries and activity
        Route::get('/recent', function () {
            return Inertia::render('security/visitors/recent');
        })->name('recent')
          ->middleware('can:manage_visitors');
    });

    // Security Reports
    Route::get('/reports', function () {
        return Inertia::render('security/reports');
    })->name('reports.index')
      ->middleware('can:view_security_reports');

    // Security Settings (for security configuration)
    Route::get('/settings', function () {
        return Inertia::render('security/settings');
    })->name('settings.index')
      ->middleware('can:manage_security_alerts');
});