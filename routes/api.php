<?php

use App\Http\Controllers\Api\FeaturesController;
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
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your mobile application.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group.
|
*/

foreach (config('tenancy.central_domains') as $domain) {
    Route::domain($domain)->group(function () {
        // Internal API routes for inter-app communication (central <-> tenant)
        Route::prefix('internal')->middleware(['throttle:300,1'])->group(function () {
            // Feature flags API for tenant apps
            Route::get('/features/{tenant}', [FeaturesController::class, 'index'])
                ->name('api.internal.features.index');
            Route::get('/features/{tenant}/{feature}', [FeaturesController::class, 'show'])
                ->name('api.internal.features.show');
        });

        // Authentication routes for mobile app
        Route::prefix('')->group(function () {
            // Login
            Route::post('/login', [AuthenticatedSessionController::class, 'store'])
                ->middleware(['guest', 'throttle:6,1'])
                ->name('api.login');

            // Logout (requires authentication)
            Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
                ->middleware(['auth:sanctum'])
                ->name('api.logout');

            // Get authenticated user
            Route::get('/user', function (Request $request) {
                return response()->json([
                    'user' => $request->user(),
                ]);
            })->middleware(['auth:sanctum'])->name('api.user');
        });

        // Protected API routes for mobile app
        Route::middleware(['auth:sanctum', 'throttle:120,1'])->group(function () {

            // === DASHBOARD & USER ===
            Route::get('/resident/dashboard', [ResidentDashboardController::class, 'apiIndex'])
                ->name('api.resident.dashboard');

            Route::get('/user/profile', function (Request $request) {
                return response()->json([
                    'user' => $request->user()->load(['resident.apartment.apartmentType']),
                ]);
            })->name('api.user.profile');

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
            })->name('api.user.profile.update');

            // === FINANCIAL MANAGEMENT ===
            Route::prefix('finances')->name('api.finances.')->group(function () {
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
            Route::prefix('communications')->name('api.communications.')->group(function () {
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
            Route::prefix('visits')->name('api.visits.')->group(function () {
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
            Route::prefix('maintenance')->name('api.maintenance.')->group(function () {
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

            // === RESIDENT MAINTENANCE (for mobile app compatibility) ===
            Route::prefix('resident/maintenance')->name('api.resident.maintenance.')->group(function () {
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
            Route::prefix('notifications')->name('api.notifications.')->group(function () {
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
            Route::prefix('support')->name('api.support.')->group(function () {
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
            Route::prefix('reservations')->name('api.reservations.')->group(function () {
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

            // === ASSEMBLIES & VOTING (with feature flag middleware) ===
            Route::prefix('assemblies')->name('api.assemblies.')
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

            // === FEATURES ===
            Route::get('/features', [\App\Http\Controllers\Api\FeatureController::class, 'index'])
                ->name('features.index');
            Route::get('/features/{feature}', [\App\Http\Controllers\Api\FeatureController::class, 'show'])
                ->name('features.show');

            // === LEGACY DASHBOARD (for backward compatibility) ===
            Route::get('/dashboard', function (Request $request) {
                return response()->json([
                    'user' => $request->user()->load(['resident.apartment.apartmentType']),
                    'apartment' => $request->user()->resident?->apartment,
                    'recent_invoices' => [],
                    'pending_payments' => 0,
                    'announcements' => [],
                ]);
            })->name('api.dashboard');
        });
    });
}
