<?php

use App\Http\Controllers\SecurityVisitController;
use App\Http\Controllers\VisitController;
use Illuminate\Support\Facades\Route;

Route::prefix('visits')->name('visits.')->group(function () {
    Route::get('/', [VisitController::class, 'index'])->name('index');
    Route::get('/create', [VisitController::class, 'create'])->name('create');
    Route::post('/', [VisitController::class, 'store'])->name('store');
    Route::get('/{visit}', [VisitController::class, 'show'])->name('show');
    Route::delete('/{visit}', [VisitController::class, 'destroy'])->name('destroy');
});

Route::prefix('security')->name('security.')->group(function () {
    Route::get('/visits/scanner', [SecurityVisitController::class, 'scanner'])->name('visits.scanner');
    Route::post('/visits/validate-qr', [SecurityVisitController::class, 'validateQR'])->name('visits.validate-qr');
    Route::post('/visits/authorize-entry', [SecurityVisitController::class, 'authorizeEntry'])->name('visits.authorize-entry');
    Route::get('/visits/recent-entries', [SecurityVisitController::class, 'recentEntries'])->name('visits.recent-entries');
});
