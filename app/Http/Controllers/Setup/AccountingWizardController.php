<?php

namespace App\Http\Controllers\Setup;

use App\Http\Controllers\Controller;
use App\Models\Apartment;
use App\Models\ChartOfAccounts;
use App\Models\PaymentConcept;
use App\Models\PaymentConceptAccountMapping;
use Database\Seeders\ChartOfAccountsSeeder;
use Database\Seeders\PaymentConceptAccountMappingSeeder;
use Database\Seeders\PaymentConceptSeeder;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AccountingWizardController extends Controller
{
    public function index()
    {
        $setupStatus = [
            'has_apartments' => Apartment::exists(),
            'has_chart_of_accounts' => ChartOfAccounts::exists(),
            'has_payment_concepts' => PaymentConcept::exists(),
            'has_accounting_mappings' => PaymentConceptAccountMapping::exists(),
            'apartments_count' => Apartment::count(),
            'chart_accounts_count' => ChartOfAccounts::count(),
            'payment_concepts_count' => PaymentConcept::count(),
            'mappings_count' => PaymentConceptAccountMapping::count(),
        ];

        return Inertia::render('Setup/AccountingWizard', [
            'setup_status' => $setupStatus,
        ]);
    }

    public function quickSetup()
    {
        try {
            DB::transaction(function () {
                $results = [];

                // 1. Create Chart of Accounts if not exists
                if (! ChartOfAccounts::exists()) {
                    $seeder = new ChartOfAccountsSeeder;
                    $seeder->run();
                    $results[] = 'Plan de cuentas contable inicializado';
                }

                // 2. Create Payment Concepts if not exists
                if (! PaymentConcept::exists()) {
                    $seeder = new PaymentConceptSeeder;
                    $seeder->run();
                    $results[] = 'Conceptos de pago creados';
                }

                // 3. Create Accounting Mappings
                $mappingsBefore = PaymentConceptAccountMapping::count();
                $seeder = new PaymentConceptAccountMappingSeeder;
                $seeder->run();
                $mappingsAfter = PaymentConceptAccountMapping::count();
                $mappingsCreated = $mappingsAfter - $mappingsBefore;

                if ($mappingsCreated > 0) {
                    $results[] = "{$mappingsCreated} mapeos contables configurados";
                }

                if (empty($results)) {
                    $results[] = 'Todos los elementos ya estaban configurados';
                }

                session()->flash('success', 'Configuración rápida completada: '.implode(', ', $results));
            });

            return redirect()->route('setup.accounting-wizard.index');
        } catch (\Exception $e) {
            return back()->withErrors([
                'quick_setup' => 'Error durante la configuración rápida: '.$e->getMessage(),
            ]);
        }
    }
}
