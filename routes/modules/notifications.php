<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::get('/unread', [NotificationController::class, 'unread'])->name('unread');
    Route::get('/counts', [NotificationController::class, 'counts'])->name('counts');
    Route::patch('/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('mark-as-read');
    Route::patch('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
    Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('destroy');
});