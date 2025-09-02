<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubscriptionPaymentController;

// Public subscription routes (no auth required for payment processing)
Route::prefix('subscription')->name('subscription.')->group(function () {
    // Show subscription plans
    Route::get('/plans', [SubscriptionPaymentController::class, 'index'])->name('plans');
    
    // Create payment link
    Route::post('/payment-link', [SubscriptionPaymentController::class, 'createPaymentLink'])->name('payment-link');
    
    // Payment success/failure redirects
    Route::get('/success', [SubscriptionPaymentController::class, 'paymentSuccess'])->name('success');
    Route::get('/failure', [SubscriptionPaymentController::class, 'paymentFailure'])->name('failure');
    
    // Check transaction status
    Route::post('/check-status', [SubscriptionPaymentController::class, 'checkTransactionStatus'])->name('check-status');
    
    // Get financial institutions for PSE
    Route::get('/financial-institutions', [SubscriptionPaymentController::class, 'getFinancialInstitutions'])->name('financial-institutions');
    
    // Test Wompi connection (temporary for debugging)
    Route::get('/test-wompi', [SubscriptionPaymentController::class, 'testWompiConnection'])->name('test-wompi');
    
    // Check subscription status (temporary for debugging)
    Route::get('/check-subscription', [SubscriptionPaymentController::class, 'checkSubscriptionStatus'])->name('check-subscription');
    
    // Manual process payment (temporary for debugging)
    Route::get('/manual-process-payment', [SubscriptionPaymentController::class, 'manualProcessPayment'])->name('manual-process-payment');
});

// Wompi webhook - must be public and not have CSRF protection
Route::post('/wompi/webhook', [SubscriptionPaymentController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('wompi.webhook');