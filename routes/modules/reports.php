<?php

use App\Http\Controllers\ReportsController;
use Illuminate\Support\Facades\Route;

// Reports
Route::get('reports', [ReportsController::class, 'index'])->name('reports.index')->middleware(['rate.limit:search', 'can:view_reports']);
