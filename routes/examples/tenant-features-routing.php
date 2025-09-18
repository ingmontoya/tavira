<?php

/**
 * EJEMPLO: Aplicación del middleware RequiresFeature en rutas
 *
 * Este archivo muestra cómo proteger rutas basándose en features habilitadas para tenants.
 * Las rutas solo serán accesibles si el tenant tiene la feature específica habilitada.
 */

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas Protegidas por Features
|--------------------------------------------------------------------------
*/

// ✅ CORRESPONDENCIA - Requiere feature 'correspondence'
Route::middleware(['auth', 'requires.feature:correspondence'])->group(function () {
    Route::resource('correspondence', 'CorrespondenceController');
    Route::get('correspondence/{correspondence}/download', 'CorrespondenceController@download')
        ->name('correspondence.download');
});

// ✅ SOLICITUDES DE MANTENIMIENTO - Requiere feature 'maintenance_requests'
Route::middleware(['auth', 'requires.feature:maintenance_requests'])->group(function () {
    Route::resource('maintenance-requests', 'MaintenanceRequestController');
    Route::resource('maintenance-categories', 'MaintenanceCategoryController');
    Route::resource('maintenance-staff', 'MaintenanceStaffController');
    Route::get('maintenance-requests-calendar', 'MaintenanceRequestController@calendar')
        ->name('maintenance-requests.calendar');
});

// ✅ GESTIÓN DE VISITANTES - Requiere feature 'visitor_management'
Route::middleware(['auth', 'requires.feature:visitor_management'])->group(function () {
    Route::resource('visits', 'VisitController');
    Route::post('visits/{visit}/approve', 'VisitController@approve')->name('visits.approve');
    Route::get('security/visits/scanner', 'SecurityController@visitScanner')->name('security.visits.scanner');
    Route::get('security/visits/recent-entries', 'SecurityController@recentEntries')->name('security.visits.recent-entries');
});

// ✅ CONTABILIDAD - Requiere feature 'accounting'
Route::middleware(['auth', 'requires.feature:accounting'])->group(function () {
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::resource('chart-of-accounts', 'ChartOfAccountsController');
        Route::resource('transactions', 'AccountingTransactionController');
        Route::resource('budgets', 'BudgetController');
        Route::get('reports', 'AccountingReportController@index')->name('reports.index');
    });

    // Finanzas también requieren contabilidad
    Route::get('account-statement', 'FinanceController@accountStatement')->name('account-statement');
    Route::resource('finance/payments', 'PaymentController');
    Route::resource('invoices', 'InvoiceController');
});

// ✅ RESERVAS - Requiere feature 'reservations'
Route::middleware(['auth', 'requires.feature:reservations'])->group(function () {
    Route::resource('reservations', 'ReservationController');
    Route::resource('reservable-assets', 'ReservableAssetController');
});

// ✅ ANUNCIOS - Requiere feature 'announcements'
Route::middleware(['auth', 'requires.feature:announcements'])->group(function () {
    Route::resource('announcements', 'AnnouncementController');
    Route::get('resident/announcements', 'AnnouncementController@residentIndex')
        ->name('resident.announcements');
});

// ✅ DOCUMENTOS - Requiere feature 'documents'
Route::middleware(['auth', 'requires.feature:documents'])->group(function () {
    Route::resource('documents', 'DocumentController');
    Route::resource('minutes', 'MinuteController');
});

// ✅ TICKETS DE SOPORTE/PQRS - Requiere feature 'support_tickets'
Route::middleware(['auth', 'requires.feature:support_tickets'])->group(function () {
    Route::resource('pqrs', 'PQRSController');
    Route::resource('messages', 'MessageController');
});

// ✅ ACUERDOS DE PAGO - Requiere feature 'payment_agreements'
Route::middleware(['auth', 'requires.feature:payment_agreements'])->group(function () {
    Route::resource('payment-agreements', 'PaymentAgreementController');
    Route::post('payment-agreements/{agreement}/approve', 'PaymentAgreementController@approve')
        ->name('payment-agreements.approve');
});

/*
|--------------------------------------------------------------------------
| Rutas con Múltiples Features (Ejemplo Avanzado)
|--------------------------------------------------------------------------
*/

// Requiere TANTO correspondencia COMO anuncios
Route::middleware(['auth'])->group(function () {
    Route::get('communication/dashboard', function () {
        // Esta ruta podría verificar múltiples features en el controlador
        return view('communication.dashboard');
    })->middleware(['requires.feature:correspondence', 'requires.feature:announcements']);
});

/*
|--------------------------------------------------------------------------
| API Routes con Features
|--------------------------------------------------------------------------
*/

Route::prefix('api')->middleware('auth:sanctum')->group(function () {
    // API de correspondencia
    Route::middleware('requires.feature:correspondence')->group(function () {
        Route::apiResource('correspondence', 'Api\CorrespondenceController');
    });

    // API de mantenimiento
    Route::middleware('requires.feature:maintenance_requests')->group(function () {
        Route::apiResource('maintenance-requests', 'Api\MaintenanceRequestController');
    });

    // API de visitantes
    Route::middleware('requires.feature:visitor_management')->group(function () {
        Route::apiResource('visits', 'Api\VisitController');
    });
});

/*
|--------------------------------------------------------------------------
| Verificación Condicional en Controladores
|--------------------------------------------------------------------------
*/

/**
 * EJEMPLO: Cómo verificar features en controladores
 *
 * class ExampleController extends Controller
 * {
 *     public function index()
 *     {
 *         $tenantId = tenant('id');
 *
 *         // Verificación manual de feature
 *         if (!TenantFeature::isFeatureEnabled($tenantId, 'correspondence')) {
 *             abort(403, 'Correspondencia no está habilitada para este tenant');
 *         }
 *
 *         return view('correspondence.index');
 *     }
 *
 *     public function dashboard()
 *     {
 *         $features = [
 *             'hasCorrespondence' => TenantFeature::isFeatureEnabled(tenant('id'), 'correspondence'),
 *             'hasMaintenace' => TenantFeature::isFeatureEnabled(tenant('id'), 'maintenance_requests'),
 *             'hasReservations' => TenantFeature::isFeatureEnabled(tenant('id'), 'reservations'),
 *         ];
 *
 *         return Inertia::render('Dashboard', compact('features'));
 *     }
 * }
 */
