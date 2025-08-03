<?php

use App\Http\Controllers\InvitationController;
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

// Invitations Management
Route::resource('invitations', InvitationController::class)->except(['edit', 'update'])->middleware('can:manage_invitations');
Route::post('invitations/mass', [InvitationController::class, 'storeMass'])->name('invitations.mass.store')->middleware('can:manage_invitations');
Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend')->middleware('can:manage_invitations');
Route::get('invitations/{invitation}/url', [InvitationController::class, 'getRegistrationUrl'])->name('invitations.url')->middleware('can:manage_invitations');