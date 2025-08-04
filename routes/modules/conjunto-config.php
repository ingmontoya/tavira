<?php

use App\Http\Controllers\ConjuntoConfigController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Conjunto Configuration Management (Single conjunto)
Route::get('conjunto-config', [ConjuntoConfigController::class, 'index'])->name('conjunto-config.index')->middleware('can:view_conjunto_config');
Route::get('conjunto-config/create', [ConjuntoConfigController::class, 'create'])->name('conjunto-config.create')->middleware('can:edit_conjunto_config');
Route::post('conjunto-config', [ConjuntoConfigController::class, 'store'])->name('conjunto-config.store')->middleware('can:edit_conjunto_config');
Route::get('conjunto-config/show', [ConjuntoConfigController::class, 'show'])->name('conjunto-config.show')->middleware('can:view_conjunto_config');
Route::get('conjunto-config/edit', [ConjuntoConfigController::class, 'edit'])->name('conjunto-config.edit')->middleware('can:edit_conjunto_config');
Route::put('conjunto-config', [ConjuntoConfigController::class, 'update'])->name('conjunto-config.update')->middleware('can:edit_conjunto_config');
Route::post('conjunto-config/generate-apartments', [ConjuntoConfigController::class, 'generateApartments'])
    ->name('conjunto-config.generate-apartments')->middleware('can:edit_conjunto_config');

// Conjuntos Management
Route::get('conjuntos', function () {
    return Inertia::render('Conjuntos/Index');
})->name('conjuntos.index')->middleware(['rate.limit:default', 'can:view_conjunto_config']);
