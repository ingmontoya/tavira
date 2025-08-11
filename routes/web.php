<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Include module route files outside of middleware groups
require __DIR__.'/modules/placeholder-modules.php';

Route::middleware(['auth', 'verified'])->group(function () {
    // Include authenticated module route files
    require __DIR__.'/modules/dashboard.php';
    require __DIR__.'/modules/reports.php';
    require __DIR__.'/modules/residents.php';
    require __DIR__.'/modules/conjunto-config.php';
    require __DIR__.'/modules/apartments.php';
    require __DIR__.'/modules/finance.php';
    require __DIR__.'/modules/accounting.php';
    require __DIR__.'/modules/communication.php';
    require __DIR__.'/modules/visits.php';
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
