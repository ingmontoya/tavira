<?php

namespace Database\Seeders;

use App\Models\AccountingTransaction;
use App\Models\AccountingTransactionEntry;
use App\Models\Apartment;
use App\Models\ApartmentType;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentApplication;
use App\Models\PaymentConcept;
use App\Models\Resident;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ComprehensiveTestSeeder extends Seeder
{
    /**
     * Run the comprehensive test scenario.
     */
    public function run(): void
    {
        DB::beginTransaction();

        try {
            $this->command->info('🧹 Clearing existing data...');
            $this->clearExistingData();

            $this->command->info('🏢 Creating conjunto configuration...');
            $conjunto = $this->createConjuntoConfig();

            $this->command->info('📊 Creating chart of accounts...');
            $this->call(ChartOfAccountsSeeder::class);

            $this->command->info('🏠 Creating apartment types...');
            $apartmentTypes = $this->createApartmentTypes($conjunto);

            $this->command->info('🏘️ Creating 100 apartments...');
            $apartments = $this->createApartments($conjunto, $apartmentTypes);

            $this->command->info('👥 Creating residents...');
            $this->createResidents($apartments);

            $this->command->info('💰 Creating payment concepts...');
            $paymentConcepts = $this->createPaymentConcepts($conjunto);

            $this->command->info('🏦 Creating payment method account mappings...');
            $this->createPaymentMethodMappings($conjunto);

            $this->command->info('📋 Generating invoices for all apartments...');
            $invoices = $this->generateInvoices($apartments, $paymentConcepts);

            $this->command->info('💳 Creating payment scenarios...');
            $this->createPaymentScenarios($invoices);

            $this->command->info('📊 Updating invoice statuses...');
            $this->updateInvoiceStatuses($invoices);

            // Note: Accounting transactions are normally generated automatically via events
            // but we're skipping them in this test seeder for simplicity
            $this->command->info('ℹ️  Accounting transactions skipped (normally auto-generated via events)');

            DB::commit();

            $this->command->info('✅ Comprehensive test scenario created successfully!');
            $this->printSummary($conjunto, $apartments, $invoices);

        } catch (\Exception $e) {
            DB::rollback();
            $this->command->error('❌ Error creating test scenario: '.$e->getMessage());
            throw $e;
        }
    }

    private function clearExistingData(): void
    {
        // Clear in correct order due to foreign key constraints
        // PostgreSQL doesn't have FOREIGN_KEY_CHECKS, so we delete in proper order
        // DON'T delete ChartOfAccounts as they are needed for accounting transactions

        AccountingTransactionEntry::query()->delete();
        AccountingTransaction::query()->delete();
        PaymentApplication::query()->delete();
        Payment::query()->delete();
        InvoiceItem::query()->delete();
        Invoice::query()->delete();
        Resident::query()->delete();
        Apartment::query()->delete();
        ApartmentType::query()->delete();
        ConjuntoConfig::query()->delete();

        // Keep PaymentConcepts too as they might be referenced
        PaymentConcept::query()->delete();
    }

    private function createConjuntoConfig(): ConjuntoConfig
    {
        return ConjuntoConfig::create([
            'name' => 'Conjunto Residencial Tavira Test',
            'description' => 'Conjunto residencial de prueba con 100 apartamentos distribuidos en 5 torres',
            'number_of_towers' => 5,
            'floors_per_tower' => 4,
            'apartments_per_floor' => 5,
            'is_active' => true,
            'tower_names' => ['Torre A', 'Torre B', 'Torre C', 'Torre D', 'Torre E'],
            'configuration_metadata' => [
                'floor_configuration' => [
                    1 => ['apartments_count' => 5, 'apartment_type' => 'Tipo A'],
                    2 => ['apartments_count' => 5, 'apartment_type' => 'Tipo B'],
                    3 => ['apartments_count' => 5, 'apartment_type' => 'Tipo C'],
                    4 => ['apartments_count' => 5, 'apartment_type' => 'Penthouse'],
                ],
                'building_configuration' => [
                    'has_elevator' => true,
                    'has_parking' => true,
                    'common_areas' => ['pool', 'gym', 'playground', 'social_room'],
                ],
                'contact_info' => [
                    'address' => 'Calle 123 #45-67, Bogotá, Colombia',
                    'phone' => '+57 1 234-5678',
                    'email' => 'admin@Taviratest.com',
                    'nit' => '900123456-7',
                ],
            ],
        ]);
    }

    private function createApartmentTypes(ConjuntoConfig $conjunto): array
    {
        $types = [
            [
                'name' => 'Tipo A',
                'bedrooms' => 1,
                'bathrooms' => 1,
                'area_sqm' => 45.0,
                'administration_fee' => 180000.00,
                'description' => 'Apartamento de 1 habitación, ideal para personas solteras o parejas jóvenes',
                'floor_positions' => [1, 2, 3, 4, 5],
            ],
            [
                'name' => 'Tipo B',
                'bedrooms' => 2,
                'bathrooms' => 2,
                'area_sqm' => 65.0,
                'administration_fee' => 250000.00,
                'description' => 'Apartamento de 2 habitaciones, perfecto para familias pequeñas',
                'floor_positions' => [1, 2, 3, 4, 5],
            ],
            [
                'name' => 'Tipo C',
                'bedrooms' => 3,
                'bathrooms' => 2,
                'area_sqm' => 85.0,
                'administration_fee' => 320000.00,
                'description' => 'Apartamento espacioso de 3 habitaciones para familias grandes',
                'floor_positions' => [1, 2, 3, 4, 5],
            ],
            [
                'name' => 'Penthouse',
                'bedrooms' => 3,
                'bathrooms' => 3,
                'area_sqm' => 120.0,
                'administration_fee' => 480000.00,
                'description' => 'Penthouse de lujo con terraza privada y acabados premium',
                'floor_positions' => [1, 2, 3, 4, 5],
            ],
        ];

        $apartmentTypes = [];
        foreach ($types as $type) {
            $apartmentTypes[] = ApartmentType::create([
                'conjunto_config_id' => $conjunto->id,
                'name' => $type['name'],
                'description' => $type['description'],
                'area_sqm' => $type['area_sqm'],
                'bedrooms' => $type['bedrooms'],
                'bathrooms' => $type['bathrooms'],
                'has_balcony' => $type['name'] !== 'Tipo A',
                'has_laundry_room' => true,
                'has_maid_room' => $type['name'] === 'Penthouse',
                'coefficient' => $this->calculateCoefficient($type['area_sqm']),
                'administration_fee' => $type['administration_fee'],
                'floor_positions' => $type['floor_positions'],
                'features' => [],
            ]);
        }

        return $apartmentTypes;
    }

    private function createApartments(ConjuntoConfig $conjunto, array $apartmentTypes): array
    {
        $apartments = [];
        $towerNames = $conjunto->tower_names;

        for ($towerIndex = 0; $towerIndex < 5; $towerIndex++) {
            $towerName = $towerNames[$towerIndex];

            for ($floor = 1; $floor <= 4; $floor++) {
                for ($position = 1; $position <= 5; $position++) {
                    // Assign apartment type based on floor
                    $typeIndex = $floor - 1; // Floor 1 = Tipo A (index 0), etc.
                    $apartmentType = $apartmentTypes[$typeIndex];

                    $apartmentNumber = $towerName[0].$floor.sprintf('%02d', $position);

                    $apartments[] = Apartment::create([
                        'conjunto_config_id' => $conjunto->id,
                        'apartment_type_id' => $apartmentType->id,
                        'number' => $apartmentNumber,
                        'tower' => $towerName,
                        'floor' => $floor,
                        'position_on_floor' => $position,
                        'status' => 'Occupied',
                        'monthly_fee' => $apartmentType->administration_fee,
                        'utilities' => [],
                        'features' => [],
                        'payment_status' => 'current', // Will be updated later
                        'last_payment_date' => now()->subMonths(1),
                        'outstanding_balance' => 0.00,
                    ]);
                }
            }
        }

        return $apartments;
    }

    private function calculateCoefficient(float $area): float
    {
        // Simple coefficient calculation based on area
        $totalArea = 100 * 73.75; // 100 apartments * average area

        return round(($area / $totalArea) * 100, 6);
    }

    private function createResidents(array $apartments): void
    {
        $firstNames = ['Carlos', 'María', 'José', 'Ana', 'Luis', 'Carmen', 'Miguel', 'Isabel', 'Francisco', 'Teresa', 'Juan', 'Rosa', 'Antonio', 'Dolores', 'Manuel'];
        $lastNames = ['García', 'González', 'Rodríguez', 'Fernández', 'López', 'Martínez', 'Sánchez', 'Pérez', 'Gómez', 'Martín', 'Jiménez', 'Ruiz', 'Hernández', 'Díaz', 'Moreno'];

        foreach ($apartments as $apartment) {
            // 85% of apartments have residents
            if (rand(1, 100) <= 85) {
                $firstName = $firstNames[array_rand($firstNames)];
                $lastName = $lastNames[array_rand($lastNames)].' '.$lastNames[array_rand($lastNames)];

                Resident::create([
                    'apartment_id' => $apartment->id,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'document_type' => 'CC',
                    'document_number' => (string) rand(10000000, 99999999),
                    'phone' => '+57 3'.rand(10, 99).' '.rand(100, 999).' '.rand(1000, 9999),
                    'email' => strtolower($firstName.'.'.explode(' ', $lastName)[0].'.'.$apartment->number.'.'.rand(1000, 9999)).'@example.com',
                    'resident_type' => rand(1, 100) <= 70 ? 'Owner' : 'Tenant', // 70% owners, 30% tenants
                    'status' => 'Active',
                    'start_date' => now()->subYears(rand(1, 5))->subMonths(rand(1, 11)),
                    'documents' => [],
                ]);
            }
        }
    }

    private function createPaymentConcepts(ConjuntoConfig $conjunto): array
    {
        $concepts = [
            ['name' => 'Administración', 'type' => 'monthly_administration', 'is_recurring' => true],
            ['name' => 'Multa por ruido', 'type' => 'sanction', 'is_recurring' => false],
            ['name' => 'Multa por mascota', 'type' => 'sanction', 'is_recurring' => false],
            ['name' => 'Multa por parqueadero', 'type' => 'parking', 'is_recurring' => false],
            ['name' => 'Cuota extraordinaria', 'type' => 'special', 'is_recurring' => false],
            ['name' => 'Intereses de mora', 'type' => 'late_fee', 'is_recurring' => false],
        ];

        $paymentConcepts = [];
        foreach ($concepts as $concept) {
            $paymentConcepts[] = PaymentConcept::firstOrCreate([
                'name' => $concept['name'],
                'type' => $concept['type'],
            ], [
                'description' => $concept['name'],
                'is_recurring' => $concept['is_recurring'],
                'is_active' => true,
                'billing_cycle' => $concept['is_recurring'] ? 'monthly' : 'one_time',
            ]);
        }

        return $paymentConcepts;
    }

    private function createPaymentMethodMappings(ConjuntoConfig $conjunto): void
    {
        $paymentMethods = [
            'cash' => '110501', // Caja General
            'bank_transfer' => '111001', // Banco Principal - Cuenta Corriente
            'check' => '111001', // Banco Principal - Cuenta Corriente
            'credit_card' => '111001', // Banco Principal - Cuenta Corriente
            'debit_card' => '111001', // Banco Principal - Cuenta Corriente
            'online' => '111001', // Banco Principal - Cuenta Corriente
            'pse' => '111001', // Banco Principal - Cuenta Corriente
            'other' => '111001', // Banco Principal - Cuenta Corriente (default)
        ];

        foreach ($paymentMethods as $method => $accountCode) {
            $account = $this->getAccountByCode($accountCode, $conjunto->id);

            if ($account) {
                \App\Models\PaymentMethodAccountMapping::firstOrCreate([
                    'conjunto_config_id' => $conjunto->id,
                    'payment_method' => $method,
                ], [
                    'cash_account_id' => $account,
                    'is_active' => true,
                ]);
            }
        }
    }

    private function generateInvoices(array $apartments, array $paymentConcepts): array
    {
        $invoices = [];
        $administrationConcept = collect($paymentConcepts)->firstWhere('name', 'Administración');
        $lateFeesConcept = collect($paymentConcepts)->firstWhere('name', 'Intereses de mora');

        // Generate invoices for last 3 months
        for ($monthsBack = 2; $monthsBack >= 0; $monthsBack--) {
            $billingDate = now()->subMonths($monthsBack)->startOfMonth();
            $dueDate = $billingDate->copy()->addDays(15);

            foreach ($apartments as $apartment) {
                // Create monthly invoice
                $invoice = Invoice::create([
                    'apartment_id' => $apartment->id,
                    'type' => 'monthly',
                    'billing_date' => $billingDate,
                    'due_date' => $dueDate,
                    'billing_period_year' => $billingDate->year,
                    'billing_period_month' => $billingDate->month,
                    'subtotal' => 0,
                    'early_discount' => 0,
                    'late_fees' => 0,
                    'total_amount' => 0,
                    'paid_amount' => 0,
                    'balance_amount' => 0,
                    'status' => 'pending',
                ]);

                // Add administration fee
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'payment_concept_id' => $administrationConcept->id,
                    'description' => 'Administración '.$billingDate->format('F Y'),
                    'quantity' => 1,
                    'unit_price' => $apartment->monthly_fee,
                    'total_price' => $apartment->monthly_fee,
                ]);

                // 10% chance of penalties for current month only
                if ($monthsBack == 0 && rand(1, 100) <= 10) {
                    $penaltyConcepts = collect($paymentConcepts)->whereIn('type', ['sanction', 'parking']);
                    if ($penaltyConcepts->count() > 0) {
                        $penaltyConcept = $penaltyConcepts->random();
                        $penaltyAmount = rand(50000, 200000);

                        InvoiceItem::create([
                            'invoice_id' => $invoice->id,
                            'payment_concept_id' => $penaltyConcept->id,
                            'description' => $penaltyConcept->name,
                            'quantity' => 1,
                            'unit_price' => $penaltyAmount,
                            'total_price' => $penaltyAmount,
                        ]);
                    }
                }

                $invoice->calculateTotals();
                $invoices[] = $invoice;
            }
        }

        return $invoices;
    }

    private function createPaymentScenarios(array $invoices): void
    {
        // Group invoices by apartment
        $invoicesByApartment = collect($invoices)->groupBy('apartment_id');
        $apartmentIds = $invoicesByApartment->keys()->toArray();
        shuffle($apartmentIds);

        // 70% current (paid up) - pay all invoices
        $currentCount = (int) (count($apartmentIds) * 0.7);
        $currentApartments = array_slice($apartmentIds, 0, $currentCount);

        // 20% delinquent (random aging)
        $delinquentCount = (int) (count($apartmentIds) * 0.2);
        $delinquentApartments = array_slice($apartmentIds, $currentCount, $delinquentCount);

        // 10% with penalties (paid some, have penalties)
        $penaltyApartments = array_slice($apartmentIds, $currentCount + $delinquentCount);

        // Process current apartments (70%)
        foreach ($currentApartments as $apartmentId) {
            $apartmentInvoices = $invoicesByApartment[$apartmentId];
            foreach ($apartmentInvoices as $invoice) {
                $freshInvoice = Invoice::find($invoice->id);
                $this->makePayment($freshInvoice, $freshInvoice->total_amount, 'bank_transfer');
            }
        }

        // Process delinquent apartments (20%)
        foreach ($delinquentApartments as $apartmentId) {
            $apartmentInvoices = $invoicesByApartment[$apartmentId]->sortBy('billing_date');
            $monthsToSkip = rand(1, 3); // Skip 1-3 months randomly

            $invoiceIds = $apartmentInvoices->pluck('id')->toArray();
            $paidCount = max(0, count($invoiceIds) - $monthsToSkip);

            // Pay older invoices, leave recent ones unpaid
            for ($i = 0; $i < $paidCount; $i++) {
                $invoice = Invoice::find($invoiceIds[$i]);
                $this->makePayment($invoice, $invoice->total_amount, 'cash');
            }

            // Add late fees to unpaid invoices
            for ($i = $paidCount; $i < count($invoiceIds); $i++) {
                $invoice = Invoice::find($invoiceIds[$i]);
                $daysOverdue = now()->diffInDays($invoice->due_date);
                if ($daysOverdue > 0) {
                    $lateFeeAmount = $invoice->subtotal * 0.05 * ceil($daysOverdue / 30); // 5% per month
                    $invoice->late_fees = min($lateFeeAmount, $invoice->subtotal * 0.2); // Max 20%
                    $invoice->calculateTotals();
                }
            }
        }

        // Process penalty apartments (10%)
        foreach ($penaltyApartments as $apartmentId) {
            $apartmentInvoices = $invoicesByApartment[$apartmentId];
            foreach ($apartmentInvoices as $invoice) {
                $freshInvoice = Invoice::find($invoice->id);
                // Pay base amount but leave penalties unpaid if any
                $baseAmount = $freshInvoice->subtotal;
                if ($freshInvoice->total_amount > $baseAmount) {
                    $this->makePayment($freshInvoice, $baseAmount, 'online');
                } else {
                    $this->makePayment($freshInvoice, $freshInvoice->total_amount, 'online');
                }
            }
        }
    }

    private function makePayment(Invoice $invoice, float $amount, string $method): void
    {
        $payment = Payment::create([
            'conjunto_config_id' => $invoice->apartment->conjunto_config_id,
            'apartment_id' => $invoice->apartment_id,
            'total_amount' => $amount,
            'applied_amount' => 0,
            'remaining_amount' => $amount,
            'payment_date' => now()->subDays(rand(0, 30)),
            'payment_method' => $method,
            'reference_number' => 'REF-'.strtoupper(uniqid()),
            'notes' => 'Pago generado automáticamente para pruebas',
            'status' => 'pending',
            'created_by' => 1,
        ]);

        PaymentApplication::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount_applied' => $amount,
            'applied_date' => now(),
            'created_by' => 1,
            'status' => 'active',
        ]);

        // Update invoice manually to avoid status issues
        $invoice->paid_amount += $amount;
        $invoice->balance_amount = $invoice->total_amount - $invoice->paid_amount;
        $invoice->last_payment_date = now();
        $invoice->payment_method = $method;
        $invoice->payment_reference = $payment->reference_number;

        if ($invoice->balance_amount <= 0) {
            $invoice->status = 'paid';
            $invoice->balance_amount = 0;
        } elseif ($invoice->paid_amount > 0) {
            $invoice->status = 'partial';
        }

        $invoice->save();
    }

    private function updateInvoiceStatuses(array $invoices): void
    {
        foreach ($invoices as $invoice) {
            $invoice->refresh();

            // Update apartment payment status based on current status
            $apartment = $invoice->apartment;

            // Set payment status based on most recent unpaid invoice
            $unpaidInvoices = Invoice::where('apartment_id', $apartment->id)
                ->whereIn('status', ['pending', 'overdue', 'partial'])
                ->orderBy('due_date', 'desc')
                ->get();

            if ($unpaidInvoices->isEmpty()) {
                $apartment->payment_status = 'current';
            } else {
                $mostRecentUnpaid = $unpaidInvoices->first();
                $daysOverdue = now()->diffInDays($mostRecentUnpaid->due_date);

                if ($daysOverdue <= 30) {
                    $apartment->payment_status = 'overdue_30';
                } elseif ($daysOverdue <= 60) {
                    $apartment->payment_status = 'overdue_60';
                } elseif ($daysOverdue <= 90) {
                    $apartment->payment_status = 'overdue_90';
                } else {
                    $apartment->payment_status = 'overdue_90_plus';
                }
            }

            $apartment->save();
        }
    }

    private function printSummary(ConjuntoConfig $conjunto, array $apartments, array $invoices): void
    {
        $totalInvoices = count($invoices);
        $paidInvoices = collect($invoices)->where('status', 'pagado')->count();
        $partialInvoices = collect($invoices)->where('status', 'pago_parcial')->count();
        $overdueInvoices = collect($invoices)->where('status', 'vencido')->count();
        $pendingInvoices = collect($invoices)->where('status', 'pendiente')->count();

        $totalAmount = collect($invoices)->sum('total_amount');
        $paidAmount = collect($invoices)->sum('paid_amount');
        $balanceAmount = collect($invoices)->sum('balance_amount');

        $this->command->info("\n📊 RESUMEN DEL ESCENARIO DE PRUEBAS");
        $this->command->info('================================');
        $this->command->info("🏢 Conjunto: {$conjunto->name}");
        $this->command->info('🏠 Total apartamentos: '.count($apartments));
        $this->command->info("📋 Total facturas: {$totalInvoices}");
        $this->command->info('');
        $this->command->info('💰 Estados de facturación:');
        $this->command->info("  ✅ Pagadas: {$paidInvoices} (".round(($paidInvoices / $totalInvoices) * 100, 1).'%)');
        $this->command->info("  🔶 Pago parcial: {$partialInvoices} (".round(($partialInvoices / $totalInvoices) * 100, 1).'%)');
        $this->command->info("  ❌ Vencidas: {$overdueInvoices} (".round(($overdueInvoices / $totalInvoices) * 100, 1).'%)');
        $this->command->info("  ⏳ Pendientes: {$pendingInvoices} (".round(($pendingInvoices / $totalInvoices) * 100, 1).'%)');
        $this->command->info('');
        $this->command->info('💵 Montos:');
        $this->command->info('  Total facturado: $'.number_format($totalAmount, 0));
        $this->command->info('  Total pagado: $'.number_format($paidAmount, 0));
        $this->command->info('  Saldo pendiente: $'.number_format($balanceAmount, 0));
        $this->command->info('');
    }

    private function createBasicChartOfAccounts(ConjuntoConfig $conjunto): void
    {
        $accounts = [
            // Caja y Bancos
            ['code' => '110505', 'name' => 'CAJA GENERAL', 'account_type' => 'asset', 'nature' => 'debit'],
            ['code' => '111005', 'name' => 'BANCOS', 'account_type' => 'asset', 'nature' => 'debit'],

            // Cartera
            ['code' => '130501', 'name' => 'CARTERA ADMINISTRACIÓN', 'account_type' => 'asset', 'nature' => 'debit'],
            ['code' => '130502', 'name' => 'CARTERA CUOTAS EXTRAORDINARIAS', 'account_type' => 'asset', 'nature' => 'debit'],
            ['code' => '130503', 'name' => 'CARTERA INTERESES MORA', 'account_type' => 'asset', 'nature' => 'debit'],

            // Ingresos
            ['code' => '413501', 'name' => 'CUOTAS DE ADMINISTRACIÓN', 'account_type' => 'income', 'nature' => 'credit'],
            ['code' => '413502', 'name' => 'CUOTAS EXTRAORDINARIAS', 'account_type' => 'income', 'nature' => 'credit'],
            ['code' => '413503', 'name' => 'PARQUEADEROS', 'account_type' => 'income', 'nature' => 'credit'],
            ['code' => '413505', 'name' => 'MULTAS Y SANCIONES', 'account_type' => 'income', 'nature' => 'credit'],
            ['code' => '413506', 'name' => 'INTERESES DE MORA', 'account_type' => 'income', 'nature' => 'credit'],
        ];

        foreach ($accounts as $accountData) {
            ChartOfAccounts::create([
                'conjunto_config_id' => $conjunto->id,
                'code' => $accountData['code'],
                'name' => $accountData['name'],
                'description' => $accountData['name'],
                'account_type' => $accountData['account_type'],
                'parent_id' => null,
                'level' => 6, // Detail level
                'is_active' => true,
                'requires_third_party' => in_array($accountData['code'], ['130501', '130502', '130503']),
                'nature' => $accountData['nature'],
                'accepts_posting' => true,
            ]);
        }
    }

    private function generateAccountingTransactions(ConjuntoConfig $conjunto, array $invoices): void
    {
        foreach ($invoices as $invoice) {
            $invoice = Invoice::with(['items.paymentConcept', 'apartment'])->find($invoice->id);

            // Crear transacciones contables para facturación
            $this->createInvoiceAccountingTransaction($conjunto, $invoice);

            // Si la factura está pagada, crear transacciones de pago
            if ($invoice->status === 'paid') {
                $this->createPaymentAccountingTransactions($conjunto, $invoice);
            }
        }
    }

    private function createInvoiceAccountingTransaction(ConjuntoConfig $conjunto, Invoice $invoice): void
    {
        // Agrupar items por tipo de concepto
        $itemsByConceptType = $invoice->items->groupBy(function ($item) {
            return $item->paymentConcept ? $item->paymentConcept->type : 'monthly_administration';
        });

        foreach ($itemsByConceptType as $conceptType => $items) {
            $totalAmount = $items->sum('total_price');

            $transaction = AccountingTransaction::create([
                'conjunto_config_id' => $conjunto->id,
                'transaction_date' => $invoice->billing_date,
                'description' => "Apto {$invoice->apartment->number} - Factura {$invoice->invoice_number} - {$this->getConceptTypeLabel($conceptType)}",
                'reference_type' => 'invoice',
                'reference_id' => $invoice->id,
                'total_debit' => 0,
                'total_credit' => 0,
                'status' => 'draft',
                'created_by' => 1,
            ]);

            $accounts = $this->getAccountsForConceptType($conceptType, $conjunto->id);

            // Débito: Cartera
            $transaction->addEntry([
                'account_id' => $accounts['receivable_account'],
                'description' => "Cartera {$this->getConceptTypeLabel($conceptType)} - {$invoice->invoice_number}",
                'debit_amount' => $totalAmount,
                'credit_amount' => 0,
                'third_party_type' => 'apartment',
                'third_party_id' => $invoice->apartment_id,
            ]);

            // Crédito: Ingreso
            $transaction->addEntry([
                'account_id' => $accounts['income_account'],
                'description' => "Ingreso {$this->getConceptTypeLabel($conceptType)} - {$invoice->invoice_number}",
                'debit_amount' => 0,
                'credit_amount' => $totalAmount,
            ]);

            // Contabilizar la transacción
            $transaction->post();
        }
    }

    private function createPaymentAccountingTransactions(ConjuntoConfig $conjunto, Invoice $invoice): void
    {
        $paymentApplications = PaymentApplication::where('invoice_id', $invoice->id)->with('payment')->get();

        foreach ($paymentApplications as $application) {
            $payment = $application->payment;

            $transaction = AccountingTransaction::create([
                'conjunto_config_id' => $conjunto->id,
                'transaction_date' => $payment->payment_date,
                'description' => "Pago Apto {$invoice->apartment->number} - {$payment->reference_number}",
                'reference_type' => 'payment',
                'reference_id' => $payment->id,
                'total_debit' => 0,
                'total_credit' => 0,
                'status' => 'draft',
                'created_by' => 1,
            ]);

            $cashAccount = \App\Models\PaymentMethodAccountMapping::getCashAccountForPaymentMethod($conjunto->id, $payment->payment_method);
            $receivableAccount = $this->getAccountByCode('130501', $conjunto->id); // Cartera Administración

            // Débito: Efectivo/Banco
            $transaction->addEntry([
                'account_id' => $cashAccount->id,
                'description' => "Pago {$payment->payment_method} - {$payment->reference_number}",
                'debit_amount' => $application->amount_applied,
                'credit_amount' => 0,
            ]);

            // Crédito: Cartera
            $transaction->addEntry([
                'account_id' => $receivableAccount,
                'description' => "Abono cartera Apto {$invoice->apartment->number}",
                'debit_amount' => 0,
                'credit_amount' => $application->amount_applied,
                'third_party_type' => 'apartment',
                'third_party_id' => $invoice->apartment_id,
            ]);

            // Contabilizar la transacción
            $transaction->post();
        }
    }

    private function getAccountsForConceptType(string $conceptType, int $conjuntoConfigId): array
    {
        $mappings = [
            'monthly_administration' => [
                'receivable_account' => $this->getAccountByCode('130501', $conjuntoConfigId), // Cartera Administración
                'income_account' => $this->getAccountByCode('413501', $conjuntoConfigId), // Cuotas de Administración
            ],
            'sanction' => [
                'receivable_account' => $this->getAccountByCode('130501', $conjuntoConfigId), // Cartera Administración
                'income_account' => $this->getAccountByCode('413505', $conjuntoConfigId), // Multas y Sanciones
            ],
            'parking' => [
                'receivable_account' => $this->getAccountByCode('130501', $conjuntoConfigId), // Cartera Administración
                'income_account' => $this->getAccountByCode('413503', $conjuntoConfigId), // Parqueaderos
            ],
            'late_fee' => [
                'receivable_account' => $this->getAccountByCode('130503', $conjuntoConfigId), // Cartera Intereses Mora
                'income_account' => $this->getAccountByCode('413506', $conjuntoConfigId), // Intereses de Mora
            ],
            'special' => [
                'receivable_account' => $this->getAccountByCode('130502', $conjuntoConfigId), // Cartera Cuotas Extraordinarias
                'income_account' => $this->getAccountByCode('413502', $conjuntoConfigId), // Cuotas Extraordinarias
            ],
        ];

        return $mappings[$conceptType] ?? $mappings['monthly_administration'];
    }

    private function getAccountByCode(string $code, ?int $conjuntoConfigId = null): int
    {
        $query = ChartOfAccounts::where('code', $code);

        // Filter by conjunto if provided
        if ($conjuntoConfigId) {
            $query->where('conjunto_config_id', $conjuntoConfigId);
        }

        $account = $query->first();

        if (! $account) {
            throw new \Exception("No se encontró la cuenta contable con código: {$code} para conjunto {$conjuntoConfigId}");
        }

        return $account->id;
    }

    private function getConceptTypeLabel(string $conceptType): string
    {
        $labels = [
            'monthly_administration' => 'Administración Mensual',
            'sanction' => 'Multas y Sanciones',
            'parking' => 'Parqueaderos',
            'late_fee' => 'Intereses de Mora',
            'special' => 'Cuotas Extraordinarias',
        ];

        return $labels[$conceptType] ?? 'Otros Ingresos';
    }
}
