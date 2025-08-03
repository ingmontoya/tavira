<?php

use App\Http\Controllers\ApartmentController;
use Illuminate\Support\Facades\Route;

// Apartments Management
Route::resource('apartments', ApartmentController::class)->middleware('can:view_apartments');
Route::get('apartments-delinquent', [ApartmentController::class, 'delinquent'])->name('apartments.delinquent')->middleware('can:view_apartments');
Route::get('apartments-delinquent/export-excel', [ApartmentController::class, 'exportDelinquentExcel'])->name('apartments.delinquent.export.excel')->middleware('can:view_apartments');
Route::get('apartments-delinquent/export-pdf', [ApartmentController::class, 'exportDelinquentPdf'])->name('apartments.delinquent.export.pdf')->middleware('can:view_apartments');