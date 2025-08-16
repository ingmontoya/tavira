<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\CorrespondenceController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\ResidentAnnouncementController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Correspondence Management
Route::resource('correspondence', CorrespondenceController::class)->middleware(['rate.limit:default']);
Route::post('correspondence/{correspondence}/deliver', [CorrespondenceController::class, 'markAsDelivered'])
    ->name('correspondence.deliver')->middleware(['rate.limit:default']);
Route::delete('correspondence/attachments/{attachment}', [CorrespondenceController::class, 'deleteAttachment'])
    ->name('correspondence.attachments.destroy')->middleware(['rate.limit:default']);

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

// Email Management
Route::prefix('email')->name('email.')->group(function () {
    // Admin Email Routes
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [EmailController::class, 'adminIndex'])->name('index')->middleware(['rate.limit:default', 'can:view_admin_email']);
        Route::get('/compose', [EmailController::class, 'adminCompose'])->name('compose')->middleware(['rate.limit:default', 'can:create_admin_email']);
        Route::post('/send', [EmailController::class, 'send'])->name('send')->middleware(['rate.limit:default', 'can:create_admin_email']);
        Route::get('/{id}', [EmailController::class, 'adminShow'])->name('show')->middleware(['rate.limit:default', 'can:view_admin_email']);
        Route::delete('/{id}', [EmailController::class, 'destroy'])->name('destroy')->middleware(['rate.limit:default', 'can:delete_admin_email']);
        Route::post('/{id}/read', [EmailController::class, 'markAsRead'])->name('read')->middleware(['rate.limit:default', 'can:edit_admin_email']);
        Route::post('/{id}/unread', [EmailController::class, 'markAsUnread'])->name('unread')->middleware(['rate.limit:default', 'can:edit_admin_email']);
    });

    // Concejo Email Routes
    Route::prefix('concejo')->name('concejo.')->group(function () {
        Route::get('/', [EmailController::class, 'concejoIndex'])->name('index')->middleware(['rate.limit:default', 'can:view_council_email']);
        Route::get('/compose', [EmailController::class, 'concejoCompose'])->name('compose')->middleware(['rate.limit:default', 'can:create_council_email']);
        Route::post('/send', [EmailController::class, 'send'])->name('send')->middleware(['rate.limit:default', 'can:create_council_email']);
        Route::get('/{id}', [EmailController::class, 'concejoShow'])->name('show')->middleware(['rate.limit:default', 'can:view_council_email']);
        Route::delete('/{id}', [EmailController::class, 'destroy'])->name('destroy')->middleware(['rate.limit:default', 'can:delete_council_email']);
        Route::post('/{id}/read', [EmailController::class, 'markAsRead'])->name('read')->middleware(['rate.limit:default', 'can:edit_council_email']);
        Route::post('/{id}/unread', [EmailController::class, 'markAsUnread'])->name('unread')->middleware(['rate.limit:default', 'can:edit_council_email']);
    });
});

// Email Templates Management
Route::resource('email-templates', EmailTemplateController::class)->middleware(['rate.limit:default', 'can:manage_email_templates']);
Route::post('email-templates/{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])->name('email-templates.preview')->middleware(['rate.limit:default', 'can:manage_email_templates']);
Route::post('email-templates/{emailTemplate}/set-default', [EmailTemplateController::class, 'setDefault'])->name('email-templates.set-default')->middleware(['rate.limit:default', 'can:manage_email_templates']);
Route::post('email-templates/{emailTemplate}/toggle-status', [EmailTemplateController::class, 'toggleStatus'])->name('email-templates.toggle-status')->middleware(['rate.limit:default', 'can:manage_email_templates']);
Route::post('email-templates/{emailTemplate}/duplicate', [EmailTemplateController::class, 'duplicate'])->name('email-templates.duplicate')->middleware(['rate.limit:default', 'can:manage_email_templates']);

// Invitations Management
Route::resource('invitations', InvitationController::class)->except(['edit', 'update'])->middleware('can:manage_invitations');
Route::post('invitations/mass', [InvitationController::class, 'storeMass'])->name('invitations.mass.store')->middleware('can:manage_invitations');
Route::post('invitations/{invitation}/resend', [InvitationController::class, 'resend'])->name('invitations.resend')->middleware('can:manage_invitations');
Route::get('invitations/{invitation}/url', [InvitationController::class, 'getRegistrationUrl'])->name('invitations.url')->middleware('can:manage_invitations');
