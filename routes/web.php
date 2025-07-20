<?php

use App\Http\Controllers\Student;
use App\Http\Controllers\Program;
use App\Http\Controllers\Semester;
use App\Http\Controllers\StudyPlanController;
use App\Http\Controllers\PeriodController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ApartmentController;
use App\Http\Controllers\ConjuntoConfigController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');


Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('users', function () {
        return Inertia::render('Users/Index');
    })->name('users.index')->middleware('rate.limit:strict');

    // Reports
    Route::get('reports', [ReportsController::class, 'index'])->name('reports.index')->middleware('rate.limit:search');

    // Residents Management
    Route::resource('residents', ResidentController::class);

    // Conjunto Configuration Management (Single conjunto)
    Route::get('conjunto-config', [ConjuntoConfigController::class, 'index'])->name('conjunto-config.index');
    Route::get('conjunto-config/show', [ConjuntoConfigController::class, 'show'])->name('conjunto-config.show');
    Route::get('conjunto-config/edit', [ConjuntoConfigController::class, 'edit'])->name('conjunto-config.edit');
    Route::put('conjunto-config', [ConjuntoConfigController::class, 'update'])->name('conjunto-config.update');
    Route::post('conjunto-config/generate-apartments', [ConjuntoConfigController::class, 'generateApartments'])
        ->name('conjunto-config.generate-apartments');

    // Conjuntos Management
    Route::get('conjuntos', function () {
        return Inertia::render('Conjuntos/Index');
    })->name('conjuntos.index')->middleware('rate.limit:default');

    // Apartments Management
    Route::resource('apartments', ApartmentController::class);

    // Finance Management
    Route::get('finances', function () {
        return Inertia::render('Finances/Index');
    })->name('finances.index')->middleware('rate.limit:default');

    Route::get('fees', function () {
        return Inertia::render('Fees/Index');
    })->name('fees.index')->middleware('rate.limit:default');

    Route::get('payments', function () {
        return Inertia::render('Payments/Index');
    })->name('payments.index')->middleware('rate.limit:default');

    Route::get('providers', function () {
        return Inertia::render('Providers/Index');
    })->name('providers.index')->middleware('rate.limit:default');

    // Communication
    Route::get('correspondence', function () {
        return Inertia::render('Correspondence/Index');
    })->name('correspondence.index')->middleware('rate.limit:default');

    Route::get('announcements', function () {
        return Inertia::render('Announcements/Index');
    })->name('announcements.index')->middleware('rate.limit:default');

    Route::get('visits', function () {
        return Inertia::render('Visits/Index');
    })->name('visits.index')->middleware('rate.limit:default');

    // Documents
    Route::get('documents', function () {
        return Inertia::render('Documents/Index');
    })->name('documents.index')->middleware('rate.limit:default');

    Route::get('minutes', function () {
        return Inertia::render('Minutes/Index');
    })->name('minutes.index')->middleware('rate.limit:default');

    // Security
    Route::get('security', function () {
        return Inertia::render('Security/Index');
    })->name('security.main')->middleware('rate.limit:default');

    // Support
    Route::get('support', function () {
        return Inertia::render('Support/Index');
    })->name('support.index')->middleware('rate.limit:default');

    // Documentation
    Route::get('docs', function () {
        return Inertia::render('Docs/Index');
    })->name('docs.index')->middleware('rate.limit:default');

    Route::get('settings', function () {
        return Inertia::render('Settings/Index');
    })->name('settings.index')->middleware('rate.limit:default');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
