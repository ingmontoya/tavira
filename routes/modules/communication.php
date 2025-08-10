<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ResidentAnnouncementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Communication
Route::get('correspondence', function () {
    return Inertia::render('Correspondence/Index');
})->name('correspondence.index')->middleware(['rate.limit:default', 'can:view_announcements']);

Route::get('announcements', function () {
    return Inertia::render('Announcements/Index');
})->name('announcements.index')->middleware(['rate.limit:default', 'can:view_announcements']);

Route::get('visits', function () {
    return Inertia::render('Visits/Index');
})->name('visits.index')->middleware(['rate.limit:default', 'can:manage_visitors']);

// Announcements - Admin Management
Route::get('announcements', [AnnouncementController::class, 'index'])
    ->name('announcements.index')->middleware(['rate.limit:default', 'can:view_announcements']);
Route::get('announcements/create', [AnnouncementController::class, 'create'])
    ->name('announcements.create')->middleware(['rate.limit:default', 'can:create_announcements']);
Route::post('announcements', [AnnouncementController::class, 'store'])
    ->name('announcements.store')->middleware(['rate.limit:default', 'can:create_announcements']);
Route::get('announcements/{announcement}', [AnnouncementController::class, 'show'])
    ->name('announcements.show')->middleware(['rate.limit:default', 'can:view_announcements']);
Route::get('announcements/{announcement}/edit', [AnnouncementController::class, 'edit'])
    ->name('announcements.edit')->middleware(['rate.limit:default', 'can:edit_announcements']);
Route::put('announcements/{announcement}', [AnnouncementController::class, 'update'])
    ->name('announcements.update')->middleware(['rate.limit:default', 'can:edit_announcements']);
Route::delete('announcements/{announcement}', [AnnouncementController::class, 'destroy'])
    ->name('announcements.destroy')->middleware(['rate.limit:default', 'can:edit_announcements']);

Route::get('announcements/{announcement}/confirmations', [AnnouncementController::class, 'confirmations'])
    ->name('announcements.confirmations')->middleware(['rate.limit:default', 'can:view_announcements']);
Route::post('announcements/{announcement}/duplicate', [AnnouncementController::class, 'duplicate'])
    ->name('announcements.duplicate')->middleware(['rate.limit:default', 'can:create_announcements']);

// Announcements - Resident View
Route::prefix('resident')->name('resident.')->group(function () {
    Route::get('announcements', [ResidentAnnouncementController::class, 'index'])
        ->name('announcements.index')->middleware(['rate.limit:default']);
    Route::get('announcements/{announcement}', [ResidentAnnouncementController::class, 'show'])
        ->name('announcements.show')->middleware(['rate.limit:default']);
    Route::post('announcements/{announcement}/confirm', [ResidentAnnouncementController::class, 'confirm'])
        ->name('announcements.confirm')->middleware(['rate.limit:default']);
});

// Invitations Management
Route::resource('invitations', InvitationController::class)->except(['edit', 'update'])->middleware('can:manage_invitations');
Route::post('invitations/mass', [InvitationController::class, 'storeMass'])->name('invitations.mass.store')->middleware('can:manage_invitations');
Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend')->middleware('can:manage_invitations');
Route::get('invitations/{invitation}/url', [InvitationController::class, 'getRegistrationUrl'])->name('invitations.url')->middleware('can:manage_invitations');
