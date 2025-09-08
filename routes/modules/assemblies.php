<?php

use App\Http\Controllers\AssemblyController;
use App\Http\Controllers\VoteController;
use App\Http\Controllers\VoteDelegateController;
use App\Http\Middleware\RequiresFeature;
use Illuminate\Support\Facades\Route;

// Voting/Assembly routes with feature flag middleware
Route::middleware([RequiresFeature::class . ':voting'])->group(function () {
    
    // Assembly routes
    Route::resource('assemblies', AssemblyController::class);
    
    // Additional assembly actions
    Route::post('assemblies/{assembly}/start', [AssemblyController::class, 'start'])
        ->name('assemblies.start');
    Route::post('assemblies/{assembly}/close', [AssemblyController::class, 'close'])
        ->name('assemblies.close');
    Route::post('assemblies/{assembly}/cancel', [AssemblyController::class, 'cancel'])
        ->name('assemblies.cancel');
    Route::post('assemblies/{assembly}/attendance', [AssemblyController::class, 'selfRegisterAttendance'])
        ->name('assemblies.attendance');
    
    // Nested vote routes within assemblies
    Route::resource('assemblies.votes', VoteController::class);
    
    // Additional vote actions
    Route::post('assemblies/{assembly}/votes/{vote}/activate', [VoteController::class, 'activate'])
        ->name('assemblies.votes.activate');
    Route::post('assemblies/{assembly}/votes/{vote}/close', [VoteController::class, 'close'])
        ->name('assemblies.votes.close');
    Route::post('assemblies/{assembly}/votes/{vote}/cast', [VoteController::class, 'cast'])
        ->name('assemblies.votes.cast');
    
    // Vote delegate routes
    Route::resource('assemblies.delegates', VoteDelegateController::class)->only([
        'index', 'store', 'show', 'update', 'destroy'
    ]);
    Route::post('assemblies/{assembly}/delegates/{delegate}/approve', [VoteDelegateController::class, 'approve'])
        ->name('assemblies.delegates.approve');
    Route::post('assemblies/{assembly}/delegates/{delegate}/revoke', [VoteDelegateController::class, 'revoke'])
        ->name('assemblies.delegates.revoke');
    
    // API routes for real-time updates
    Route::prefix('api/assemblies')->name('api.assemblies.')->group(function () {
        Route::get('{assembly}/status', [AssemblyController::class, 'status'])
            ->name('status');
        Route::get('{assembly}/votes/{vote}/results', [VoteController::class, 'results'])
            ->name('votes.results');
        Route::get('{assembly}/participants', [AssemblyController::class, 'getParticipants'])
            ->name('participants');
        Route::get('{assembly}/attendance/status', [AssemblyController::class, 'getAttendanceStatus'])
            ->name('attendance.status');
    });

});