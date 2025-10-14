<?php

use App\Http\Controllers\PqrsController;
use Illuminate\Support\Facades\Route;

// Admin routes (authentication and permission required)
Route::prefix('pqrs')->name('pqrs.')->middleware('can:manage_pqrs')->group(function () {
    Route::get('/', [PqrsController::class, 'index'])->name('index');
    Route::get('/{pqrs}', [PqrsController::class, 'show'])->name('show');
    Route::patch('/{pqrs}', [PqrsController::class, 'update'])->name('update');
    Route::delete('/{pqrs}', [PqrsController::class, 'destroy'])->name('destroy');
});
