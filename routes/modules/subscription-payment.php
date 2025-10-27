<?php

use App\Http\Controllers\SubscriptionPaymentController;
use Illuminate\Support\Facades\Route;

// Public subscription routes (no auth required for payment processing)
Route::prefix('subscription')->name('subscription.')->group(function () {
    // Show subscription plans (redirect if already subscribed)
    Route::get('/plans', [SubscriptionPaymentController::class, 'index'])
        ->middleware(['auth', 'verified', 'redirect.if.subscribed'])
        ->name('plans');

    // Create payment link
    Route::post('/payment-link', [SubscriptionPaymentController::class, 'createPaymentLink'])->name('payment-link');

    // Payment success/failure redirects
    Route::get('/success', [SubscriptionPaymentController::class, 'paymentSuccess'])->name('success');
    Route::get('/failure', [SubscriptionPaymentController::class, 'paymentFailure'])->name('failure');

    // Check transaction status
    Route::post('/check-status', [SubscriptionPaymentController::class, 'checkTransactionStatus'])->name('check-status');

    // Get financial institutions for PSE
    Route::get('/financial-institutions', [SubscriptionPaymentController::class, 'getFinancialInstitutions'])->name('financial-institutions');

});

// Wompi webhook - must be public and not have CSRF protection
Route::post('/wompi/webhook', [SubscriptionPaymentController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('wompi.webhook');
