<?php

use App\Http\Controllers\AccessRequestController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Access Request (Landing Page for registration)
Route::get('solicitar-acceso', [AccessRequestController::class, 'create'])->name('access-request.create');
Route::post('solicitar-acceso', [AccessRequestController::class, 'store'])->name('access-request.store');

// New modules - Under construction
// Account statement routes moved to routes/modules/finance.php

Route::get('visitor-invitations', function () {
    return Inertia::render('VisitorInvitations/Index');
})->name('visitor-invitations.index')->middleware('can:invite_visitors');

Route::get('notifications', function () {
    return Inertia::render('Notifications/Index');
})->name('notifications.index')->middleware('can:receive_notifications');

Route::get('pqrs', [\App\Http\Controllers\PqrsController::class, 'index'])
    ->name('pqrs.index')
    ->middleware('can:send_pqrs');

Route::get('messages', [\App\Http\Controllers\MessagesController::class, 'index'])
    ->name('messages.index')
    ->middleware('can:send_messages_to_admin');

Route::get('provider-proposals', [\App\Http\Controllers\ProviderProposalsController::class, 'index'])
    ->name('provider-proposals.index')
    ->middleware('can:review_provider_proposals');

Route::get('users', function () {
    return Inertia::render('Users/Index');
})->name('users.index')->middleware('rate.limit:strict');

// Documents
Route::get('documents', [\App\Http\Controllers\DocumentController::class, 'index'])
    ->name('documents.index')
    ->middleware(['rate.limit:default', 'can:view_announcements']);

Route::get('minutes', [\App\Http\Controllers\MinutesController::class, 'index'])
    ->name('minutes.index')
    ->middleware(['rate.limit:default', 'can:view_announcements']);

// Security
Route::get('security', function () {
    return Inertia::render('Security/Index');
})->name('security.main')->middleware(['rate.limit:default', 'can:view_access_logs']);

// Support
Route::get('support', function () {
    return Inertia::render('Support/Index');
})->name('support.index')->middleware('rate.limit:default');

// Documentation
Route::get('docs', function () {
    return Inertia::render('Docs/Index');
})->name('docs.index')->middleware('rate.limit:default');

// Settings
Route::get('settings', function () {
    return Inertia::render('Settings/Index');
})->name('settings.index')->middleware(['rate.limit:default', 'can:edit_conjunto_config']);
