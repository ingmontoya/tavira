<?php

use App\Http\Controllers\ResidentController;
use Illuminate\Support\Facades\Route;

// Residents Management
Route::resource('residents', ResidentController::class)->middleware('can:view_residents');
