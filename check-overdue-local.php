#!/usr/bin/env php
<?php

/**
 * Script para verificar facturas vencidas localmente
 * Ejecutar: php check-overdue-local.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\ConjuntoConfig;
use App\Models\Invoice;
use Carbon\Carbon;

echo "\n🔍 Verificando facturas vencidas...\n\n";

$conjunto = ConjuntoConfig::first();

if (! $conjunto) {
    echo "❌ No hay conjunto configurado\n";
    exit(1);
}

echo "📍 Conjunto: {$conjunto->name}\n";
echo "📅 Fecha actual: ".now()->format('Y-m-d')."\n";
echo "📅 Evaluando para mes: Noviembre 2025\n\n";

// Simular noviembre 2025
$currentYear = 2025;
$currentMonth = 11;
$currentMonthStart = Carbon::create($currentYear, $currentMonth, 1)->startOfMonth();

echo "=== Consulta 1: Todas las facturas no pagadas ===\n";
$allUnpaid = Invoice::whereIn('status', ['pendiente', 'vencido', 'pago_parcial'])
    ->where('balance_amount', '>', 0)
    ->get();
echo "Total: {$allUnpaid->count()} facturas\n\n";

echo "=== Consulta 2: Facturas de meses anteriores (usando period) ===\n";
$withPeriod = Invoice::where(function ($query) use ($currentYear, $currentMonth) {
    $query->where('billing_period_year', '<', $currentYear)
        ->orWhere(function ($q2) use ($currentYear, $currentMonth) {
            $q2->where('billing_period_year', '=', $currentYear)
                ->where('billing_period_month', '<', $currentMonth);
        });
})
    ->whereIn('status', ['pendiente', 'vencido', 'pago_parcial'])
    ->where('balance_amount', '>', 0)
    ->get();
echo "Total: {$withPeriod->count()} facturas\n";
foreach ($withPeriod as $inv) {
    echo "  - {$inv->invoice_number} | Período: {$inv->billing_period_year}-{$inv->billing_period_month} | Balance: \${$inv->balance_amount}\n";
}
echo "\n";

echo "=== Consulta 3: Facturas anteriores (usando billing_date) ===\n";
$withDate = Invoice::where('billing_date', '<', $currentMonthStart)
    ->whereIn('status', ['pendiente', 'vencido', 'pago_parcial'])
    ->where('balance_amount', '>', 0)
    ->get();
echo "Total: {$withDate->count()} facturas\n";
foreach ($withDate as $inv) {
    echo "  - {$inv->invoice_number} | Fecha: {$inv->billing_date} | Balance: \${$inv->balance_amount}\n";
}
echo "\n";

echo "=== Consulta 4: Combinada (la que usa el dashboard) ===\n";
$combined = Invoice::where(function ($query) use ($currentYear, $currentMonth, $currentMonthStart) {
    $query->where(function ($q) use ($currentYear, $currentMonth) {
        $q->where('billing_period_year', '<', $currentYear)
            ->orWhere(function ($q2) use ($currentYear, $currentMonth) {
                $q2->where('billing_period_year', '=', $currentYear)
                    ->where('billing_period_month', '<', $currentMonth);
            });
    })
        ->orWhere(function ($q) use ($currentMonthStart) {
            $q->whereNull('billing_period_year')
                ->where('billing_date', '<', $currentMonthStart);
        });
})
    ->whereIn('status', ['pendiente', 'vencido', 'pago_parcial'])
    ->where('balance_amount', '>', 0)
    ->with('apartment')
    ->get();

echo "Total: {$combined->count()} facturas\n";

if ($combined->isEmpty()) {
    echo "\n⚠️  NO HAY FACTURAS VENCIDAS DE MESES ANTERIORES\n";
    echo "\nPosibles razones:\n";
    echo "1. Todas las facturas están pagadas\n";
    echo "2. Solo hay facturas del mes actual (noviembre)\n";
    echo "3. Los campos billing_period_* no están poblados correctamente\n\n";

    echo "💡 Sugerencia: Crea facturas de octubre para probar:\n";
    echo "\$apartment = App\\Models\\Apartment::first();\n";
    echo "App\\Models\\Invoice::create([\n";
    echo "    'apartment_id' => \$apartment->id,\n";
    echo "    'invoice_number' => 'INV-TEST-OCT',\n";
    echo "    'type' => 'monthly',\n";
    echo "    'billing_date' => '2025-10-01',\n";
    echo "    'due_date' => '2025-10-15',\n";
    echo "    'billing_period_year' => 2025,\n";
    echo "    'billing_period_month' => 10,\n";
    echo "    'subtotal' => 500000,\n";
    echo "    'total_amount' => 500000,\n";
    echo "    'balance_amount' => 500000,\n";
    echo "    'paid_amount' => 0,\n";
    echo "    'status' => 'vencido'\n";
    echo "]);\n\n";
} else {
    $totalAmount = $combined->sum('balance_amount');
    $apartments = $combined->pluck('apartment_id')->unique()->count();

    echo "\n✅ RESULTADO:\n";
    echo "📊 Total apartamentos en mora: {$apartments}\n";
    echo "💰 Monto total adeudado: \$".number_format($totalAmount, 0)."\n";
    echo "📋 Facturas vencidas: {$combined->count()}\n\n";

    echo "Detalle:\n";
    foreach ($combined as $inv) {
        $daysOverdue = $inv->days_overdue ?? 0;
        echo "  - {$inv->invoice_number} | Apto: {$inv->apartment->full_address} | Mora: {$daysOverdue} días | Balance: \$".number_format($inv->balance_amount, 0)."\n";
    }
}

echo "\n✅ Verificación completada\n\n";
