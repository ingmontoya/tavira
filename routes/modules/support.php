<?php

use App\Http\Controllers\SupportTicketController;
use Illuminate\Support\Facades\Route;

Route::prefix('support')->name('support.')->group(function () {
    Route::get('/', [SupportTicketController::class, 'index'])->name('index');
    Route::get('/create', [SupportTicketController::class, 'create'])->name('create');
    Route::post('/', [SupportTicketController::class, 'store'])->name('store');
    Route::get('/{supportTicket}', [SupportTicketController::class, 'show'])->name('show');
    Route::patch('/{supportTicket}', [SupportTicketController::class, 'update'])->name('update');
    Route::post('/{supportTicket}/messages', [SupportTicketController::class, 'addMessage'])->name('add-message');
    Route::post('/{supportTicket}/reopen', [SupportTicketController::class, 'reopen'])->name('reopen');
});
