<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CorrespondenceController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MaintenanceRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentAgreementController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ResidentAnnouncementController;
use App\Http\Controllers\ResidentDashboardController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\VisitController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Tenant API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for tenant mobile applications.
| These routes are loaded by the TenantRouteServiceProvider within a group which
| is assigned the "api" middleware group and tenant context.
|
*/

// Authentication routes for mobile app
Route::prefix('api')->middleware(['throttle:60,1'])->group(function () {
    // Login (guest middleware)
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware(['guest', 'throttle:6,1'])
        ->name('tenant.api.login');

    // === CACHE CLEAR ENDPOINT ===
    Route::get('/debug/clear-cache', function () {
        try {
            \Illuminate\Support\Facades\Artisan::call('config:clear');
            \Illuminate\Support\Facades\Artisan::call('route:clear');
            \Illuminate\Support\Facades\Artisan::call('cache:clear');

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
                'timestamp' => now()->toISOString()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    })->name('tenant.api.debug.clear-cache');

    // Protected routes requiring authentication
    Route::middleware(['auth:sanctum'])->group(function () {
        // Logout
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
            ->name('tenant.api.logout');

        // Get authenticated user
        Route::get('/user', function (Request $request) {
            return response()->json([
                'user' => $request->user()->load(['resident.apartment.apartmentType']),
            ]);
        })->name('tenant.api.user');

        // Update user profile
        Route::put('/user/profile', function (Request $request) {
            $user = $request->user();

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|max:255|unique:users,email,' . $user->id,
                'phone' => 'sometimes|string|max:20',
            ]);

            $user->update($validated);

            return response()->json([
                'success' => true,
                'user' => $user->fresh()->load(['resident.apartment.apartmentType']),
            ]);
        })->name('tenant.api.user.profile.update');

        // === DASHBOARD & USER ===
        Route::get('/resident/dashboard', [ResidentDashboardController::class, 'apiIndex'])
            ->name('tenant.api.resident.dashboard');

        // === FINANCIAL MANAGEMENT ===
        Route::prefix('finances')->name('tenant.api.finances.')->group(function () {
            // Account statement and balance
            Route::get('/account-statement', [InvoiceController::class, 'apiAccountStatement'])
                ->name('account-statement');

            // Invoices
            Route::get('/invoices', [InvoiceController::class, 'apiResidentIndex'])
                ->name('invoices.index');
            Route::get('/invoices/{invoice}', [InvoiceController::class, 'apiShow'])
                ->name('invoices.show');
            Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])
                ->name('invoices.pdf');

            // Payments
            Route::get('/payments', [PaymentController::class, 'apiResidentIndex'])
                ->name('payments.index');
            Route::get('/payments/{payment}', [PaymentController::class, 'apiShow'])
                ->name('payments.show');

            // Payment agreements
            Route::get('/payment-agreements', [PaymentAgreementController::class, 'apiResidentIndex'])
                ->name('payment-agreements.index');
            Route::post('/payment-agreements', [PaymentAgreementController::class, 'apiStore'])
                ->name('payment-agreements.store');
            Route::get('/payment-agreements/{agreement}', [PaymentAgreementController::class, 'apiShow'])
                ->name('payment-agreements.show');
            Route::post('/payment-agreements/{agreement}/record-payment', [PaymentAgreementController::class, 'apiRecordPayment'])
                ->name('payment-agreements.record-payment');
        });

        // === COMMUNICATIONS ===
        Route::prefix('communications')->name('tenant.api.communications.')->group(function () {
            // Communication stats and activity
            Route::get('/stats', [\App\Http\Controllers\Api\CommunicationController::class, 'stats'])
                ->name('stats');
            Route::get('/recent-activity', [\App\Http\Controllers\Api\CommunicationController::class, 'recentActivity'])
                ->name('recent-activity');
            // Announcements
            Route::get('/announcements', [ResidentAnnouncementController::class, 'apiIndex'])
                ->name('announcements.index');
            Route::get('/announcements/{announcement}', [ResidentAnnouncementController::class, 'apiShow'])
                ->name('announcements.show');
            Route::post('/announcements/{announcement}/confirm', [ResidentAnnouncementController::class, 'apiConfirm'])
                ->name('announcements.confirm');

            // Correspondence
            Route::get('/correspondence', [CorrespondenceController::class, 'apiResidentIndex'])
                ->name('correspondence.index');
            Route::get('/correspondence/{correspondence}', [CorrespondenceController::class, 'apiShow'])
                ->name('correspondence.show');
            Route::post('/correspondence/{correspondence}/deliver', [CorrespondenceController::class, 'apiDeliver'])
                ->name('correspondence.deliver');
        });

        // === VISITOR MANAGEMENT ===
        Route::prefix('visits')->name('tenant.api.visits.')->group(function () {
            Route::get('/', [VisitController::class, 'apiResidentIndex'])
                ->name('index');
            Route::post('/', [VisitController::class, 'apiStore'])
                ->name('store');
            Route::get('/{visit}', [VisitController::class, 'apiShow'])
                ->name('show');
            Route::put('/{visit}', [VisitController::class, 'apiUpdate'])
                ->name('update');
            Route::delete('/{visit}', [VisitController::class, 'apiDestroy'])
                ->name('destroy');
            Route::get('/{visit}/qr-code', [VisitController::class, 'apiQrCode'])
                ->name('qr-code');
        });

        // === MAINTENANCE REQUESTS ===
        Route::prefix('maintenance')->name('tenant.api.maintenance.')->group(function () {
            Route::get('/requests', [MaintenanceRequestController::class, 'apiResidentIndex'])
                ->name('requests.index');
            Route::post('/requests', [MaintenanceRequestController::class, 'apiStore'])
                ->name('requests.store');
            Route::get('/requests/{request}', [MaintenanceRequestController::class, 'apiShow'])
                ->name('requests.show');
            Route::put('/requests/{request}', [MaintenanceRequestController::class, 'apiUpdate'])
                ->name('requests.update');
            Route::post('/requests/{request}/documents', [MaintenanceRequestController::class, 'apiUploadDocument'])
                ->name('requests.documents.upload');
        });

        // === NOTIFICATIONS ===
        Route::prefix('notifications')->name('tenant.api.notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'apiIndex'])
                ->name('index');
            Route::get('/unread', [NotificationController::class, 'apiUnread'])
                ->name('unread');
            Route::get('/counts', [NotificationController::class, 'apiCounts'])
                ->name('counts');
            Route::patch('/{id}/mark-as-read', [NotificationController::class, 'apiMarkAsRead'])
                ->name('mark-as-read');
            Route::patch('/mark-all-as-read', [NotificationController::class, 'apiMarkAllAsRead'])
                ->name('mark-all-as-read');
            Route::delete('/{id}', [NotificationController::class, 'apiDelete'])
                ->name('delete');
        });

        // === SUPPORT SYSTEM ===
        Route::prefix('support')->name('tenant.api.support.')->group(function () {
            Route::get('/tickets', [SupportTicketController::class, 'apiResidentIndex'])
                ->name('tickets.index');
            Route::post('/tickets', [SupportTicketController::class, 'apiStore'])
                ->name('tickets.store');
            Route::get('/tickets/{ticket}', [SupportTicketController::class, 'apiShow'])
                ->name('tickets.show');
            Route::post('/tickets/{ticket}/messages', [SupportTicketController::class, 'apiStoreMessage'])
                ->name('tickets.messages.store');
            Route::post('/tickets/{ticket}/reopen', [SupportTicketController::class, 'apiReopen'])
                ->name('tickets.reopen');
        });

        // === RESERVATIONS ===
        Route::prefix('reservations')->name('tenant.api.reservations.')->group(function () {
            // Reservable assets
            Route::get('/assets', [\App\Http\Controllers\Api\ReservableAssetController::class, 'index'])
                ->name('assets.index');
            Route::get('/assets/{reservableAsset}', [\App\Http\Controllers\Api\ReservableAssetController::class, 'show'])
                ->name('assets.show');
            Route::get('/assets/{reservableAsset}/availability', [\App\Http\Controllers\Api\ReservableAssetController::class, 'availability'])
                ->name('assets.availability');
            Route::get('/asset-types', [\App\Http\Controllers\Api\ReservableAssetController::class, 'types'])
                ->name('asset-types');

            // User reservations
            Route::get('/', [\App\Http\Controllers\Api\ReservationController::class, 'index'])
                ->name('index');
            Route::post('/', [\App\Http\Controllers\Api\ReservationController::class, 'store'])
                ->name('store');
            Route::get('/stats', [\App\Http\Controllers\Api\ReservationController::class, 'stats'])
                ->name('stats');
            Route::get('/upcoming', [\App\Http\Controllers\Api\ReservationController::class, 'upcoming'])
                ->name('upcoming');
            Route::get('/{reservation}', [\App\Http\Controllers\Api\ReservationController::class, 'show'])
                ->name('show');
            Route::put('/{reservation}', [\App\Http\Controllers\Api\ReservationController::class, 'update'])
                ->name('update');
            Route::delete('/{reservation}', [\App\Http\Controllers\Api\ReservationController::class, 'destroy'])
                ->name('destroy');
        });

        // === ASSEMBLIES ===
        Route::prefix('assemblies')->name('tenant.api.assemblies.')->group(function () {
            Route::get('/{assembly}/attendance/status', [\App\Http\Controllers\AssemblyController::class, 'getAttendanceStatus'])
                ->name('attendance.status');
            Route::post('/{assembly}/attendance/self-register', [\App\Http\Controllers\AssemblyController::class, 'selfRegisterAttendance'])
                ->name('attendance.self-register');
            Route::get('/{assembly}/participants', [\App\Http\Controllers\AssemblyController::class, 'getParticipants'])
                ->name('participants');
            Route::get('/{assembly}/votes/{vote}/results', [\App\Http\Controllers\VoteController::class, 'getResults'])
                ->name('votes.results');
        });

        // === FEATURES ===
        Route::get('/features', [\App\Http\Controllers\Api\FeatureController::class, 'index'])
            ->name('tenant.api.features.index');
        Route::get('/features/{feature}', [\App\Http\Controllers\Api\FeatureController::class, 'show'])
            ->name('tenant.api.features.show');

        // === SECURITY ALERTS (for global banner) ===
        Route::prefix('security')->name('tenant.api.security.')->group(function () {
            Route::get('/alerts/active', [\App\Http\Controllers\Api\PanicAlertController::class, 'active'])
                ->name('alerts.active');
        });

        // === PANIC ALERTS ===
        Route::prefix('panic-alerts')->name('tenant.api.panic-alerts.')
            ->middleware([\App\Http\Middleware\RequiresFeature::class . ':panic_button'])
            ->group(function () {
                // Trigger panic alert (with specific rate limiting)
                Route::post('/', [\App\Http\Controllers\Api\PanicAlertController::class, 'store'])
                    ->middleware([\App\Http\Middleware\RateLimitMiddleware::class . ':panic'])
                    ->name('store');

                // Cancel panic alert (within 10-second window)
                Route::patch('/{panicAlert}/cancel', [\App\Http\Controllers\Api\PanicAlertController::class, 'cancel'])
                    ->name('cancel');

                // Get user's panic alert history
                Route::get('/history', [\App\Http\Controllers\Api\PanicAlertController::class, 'history'])
                    ->name('history');

                // Security dashboard routes (for authorized personnel)
                Route::get('/', [\App\Http\Controllers\Api\PanicAlertController::class, 'index'])
                    ->name('index');
                Route::get('/active', [\App\Http\Controllers\Api\PanicAlertController::class, 'active'])
                    ->name('active');
                Route::patch('/{panicAlert}/acknowledge', [\App\Http\Controllers\Api\PanicAlertController::class, 'acknowledge'])
                    ->name('acknowledge');
                Route::patch('/{panicAlert}/resolve', [\App\Http\Controllers\Api\PanicAlertController::class, 'resolve'])
                    ->name('resolve');
            });

        // === ASSEMBLIES & VOTING (with feature flag middleware) ===
        Route::prefix('assemblies')->name('tenant.api.assemblies.')
            ->middleware([\App\Http\Middleware\RequiresFeature::class . ':voting'])
            ->group(function () {
                // Assembly listing for residents
                Route::get('/', [\App\Http\Controllers\AssemblyController::class, 'apiIndex'])
                    ->name('index');
                Route::get('/{assembly}', [\App\Http\Controllers\AssemblyController::class, 'apiShow'])
                    ->name('show');

                // Assembly attendance
                Route::get('/{assembly}/attendance/status', [\App\Http\Controllers\AssemblyController::class, 'getAttendanceStatus'])
                    ->name('attendance.status');
                Route::post('/{assembly}/attendance/self-register', [\App\Http\Controllers\AssemblyController::class, 'selfRegisterAttendance'])
                    ->name('attendance.self-register');
                Route::get('/{assembly}/participants', [\App\Http\Controllers\AssemblyController::class, 'getParticipants'])
                    ->name('participants');

                // Voting
                Route::get('/{assembly}/votes', [\App\Http\Controllers\VoteController::class, 'apiIndex'])
                    ->name('votes.index');
                Route::get('/{assembly}/votes/{vote}', [\App\Http\Controllers\VoteController::class, 'apiShow'])
                    ->name('votes.show');
                Route::post('/{assembly}/votes/{vote}/cast', [\App\Http\Controllers\VoteController::class, 'apiCast'])
                    ->name('votes.cast');
                Route::get('/{assembly}/votes/{vote}/results', [\App\Http\Controllers\VoteController::class, 'getResults'])
                    ->name('votes.results');

                // Vote delegation
                Route::get('/{assembly}/delegates', [\App\Http\Controllers\VoteDelegateController::class, 'apiIndex'])
                    ->name('delegates.index');
                Route::post('/{assembly}/delegates', [\App\Http\Controllers\VoteDelegateController::class, 'apiStore'])
                    ->name('delegates.store');
                Route::delete('/{assembly}/delegates/{delegate}', [\App\Http\Controllers\VoteDelegateController::class, 'apiDestroy'])
                    ->name('delegates.destroy');
            });

        // === LEGACY DASHBOARD (for backward compatibility) ===
        Route::get('/dashboard', function (Request $request) {
            return response()->json([
                'user' => $request->user()->load(['resident.apartment.apartmentType']),
                'apartment' => $request->user()->resident?->apartment,
                'recent_invoices' => [],
                'pending_payments' => 0,
                'announcements' => [],
            ]);
        })->name('tenant.api.dashboard');
    });
});
