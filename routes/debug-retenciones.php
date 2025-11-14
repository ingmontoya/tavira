<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

Route::get('/debug/retenciones-check', function () {
    if (! config('app.debug')) {
        abort(404);
    }

    $checks = [];

    // Check if migration was run
    try {
        $checks['withholding_certificates_table_exists'] = Schema::hasTable('withholding_certificates');
        $checks['withholding_certificate_details_table_exists'] = Schema::hasTable('withholding_certificate_details');
    } catch (\Exception $e) {
        $checks['migration_check_error'] = $e->getMessage();
    }

    // Check if models exist
    $checks['WithholdingCertificate_model_exists'] = class_exists(\App\Models\WithholdingCertificate::class);
    $checks['WithholdingCertificateDetail_model_exists'] = class_exists(\App\Models\WithholdingCertificateDetail::class);
    $checks['WithholdingCertificateService_exists'] = class_exists(\App\Services\WithholdingCertificateService::class);
    $checks['WithholdingTaxReportController_exists'] = class_exists(\App\Http\Controllers\WithholdingTaxReportController::class);

    // Check if routes are registered
    $checks['retenciones_route_exists'] = \Illuminate\Support\Facades\Route::has('retenciones.index');

    // Check if Provider has relationship
    try {
        $provider = \App\Models\Provider::first();
        if ($provider) {
            $checks['provider_has_withholdingCertificates_relation'] = method_exists($provider, 'withholdingCertificates');
        }
    } catch (\Exception $e) {
        $checks['provider_check_error'] = $e->getMessage();
    }

    // Check if there are expenses with retention
    try {
        $expensesWithRetention = DB::table('expenses')
            ->where('tax_amount', '>', 0)
            ->whereNotNull('tax_account_id')
            ->count();
        $checks['expenses_with_retention_count'] = $expensesWithRetention;
    } catch (\Exception $e) {
        $checks['expenses_check_error'] = $e->getMessage();
    }

    // Check if retention accounts exist (2365xx)
    try {
        $retentionAccounts = DB::table('chart_of_accounts')
            ->where('code', 'LIKE', '2365%')
            ->where('is_active', true)
            ->count();
        $checks['retention_accounts_count'] = $retentionAccounts;
    } catch (\Exception $e) {
        $checks['accounts_check_error'] = $e->getMessage();
    }

    return response()->json([
        'status' => 'Retenciones System Check',
        'checks' => $checks,
        'all_passed' => ! isset($checks['migration_check_error'])
            && $checks['withholding_certificates_table_exists']
            && $checks['WithholdingCertificate_model_exists']
            && $checks['retenciones_route_exists'],
    ], 200);
})->middleware('web');
