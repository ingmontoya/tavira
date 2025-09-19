<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\PaymentMethodAccountMapping;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PaymentMethodAccountMappingController extends Controller
{
    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $query = PaymentMethodAccountMapping::with(['cashAccount'])
            ->where('conjunto_config_id', $conjunto->id);

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_method', 'like', "%{$search}%")
                    ->orWhereHas('cashAccount', function ($accountQuery) use ($search) {
                        $accountQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('code', 'like', "%{$search}%");
                    });
            });
        }

        $mappings = $query->orderBy('payment_method')->paginate(15);

        // Transform the data to include payment method labels
        $mappings->getCollection()->transform(function ($mapping) {
            $mapping->payment_method_label = PaymentMethodAccountMapping::getAvailablePaymentMethods()[$mapping->payment_method] ?? $mapping->payment_method;

            return $mapping;
        });

        $filters = $request->only(['payment_method', 'is_active', 'search']);

        // If no filters are applied, return null instead of empty array
        if (empty(array_filter($filters))) {
            $filters = null;
        }

        // Check system readiness
        $hasChartOfAccounts = ChartOfAccounts::where('conjunto_config_id', $conjunto->id)->exists();
        $hasMappings = PaymentMethodAccountMapping::where('conjunto_config_id', $conjunto->id)->exists();
        $mappingsCount = PaymentMethodAccountMapping::where('conjunto_config_id', $conjunto->id)->count();

        return Inertia::render('Payments/MethodAccountMappings/Index', [
            'mappings' => $mappings,
            'filters' => $filters,
            'has_chart_of_accounts' => $hasChartOfAccounts,
            'has_mappings' => $hasMappings,
            'mappings_count' => $mappingsCount,
        ]);
    }

    public function create()
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        // Get cash accounts (assets under "DISPONIBLE" - codes starting with 1105 for cash and 1110 for banks)
        $cashAccounts = ChartOfAccounts::where('conjunto_config_id', $conjunto->id)
            ->where('is_active', true)
            ->where('account_type', 'asset')
            ->where(function ($query) {
                $query->where('code', 'like', '1105%')  // Cash accounts
                    ->orWhere('code', 'like', '1110%'); // Bank accounts
            })
            ->orderBy('code')
            ->get();

        $paymentMethods = PaymentMethodAccountMapping::getAvailablePaymentMethods();

        return Inertia::render('Payments/MethodAccountMappings/Create', [
            'cashAccounts' => $cashAccounts,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'payment_method' => [
                'required',
                Rule::in(array_keys(PaymentMethodAccountMapping::getAvailablePaymentMethods())),
                Rule::unique('payment_method_account_mappings')
                    ->where('conjunto_config_id', $conjunto->id)
                    ->where('is_active', true),
            ],
            'cash_account_id' => 'required|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ]);

        PaymentMethodAccountMapping::create([
            'conjunto_config_id' => $conjunto->id,
            'payment_method' => $validated['payment_method'],
            'cash_account_id' => $validated['cash_account_id'],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('payment-method-account-mappings.index')
            ->with('success', 'Mapeo de cuenta creado exitosamente.');
    }

    public function show(PaymentMethodAccountMapping $paymentMethodAccountMapping)
    {
        $paymentMethodAccountMapping->load(['conjuntoConfig', 'cashAccount']);
        $paymentMethodAccountMapping->payment_method_label = PaymentMethodAccountMapping::getAvailablePaymentMethods()[$paymentMethodAccountMapping->payment_method] ?? $paymentMethodAccountMapping->payment_method;

        return Inertia::render('Payments/MethodAccountMappings/Show', [
            'mapping' => $paymentMethodAccountMapping,
        ]);
    }

    public function edit(PaymentMethodAccountMapping $paymentMethodAccountMapping)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        // Get cash accounts (assets under "DISPONIBLE" - codes starting with 1105 for cash and 1110 for banks)
        $cashAccounts = ChartOfAccounts::where('conjunto_config_id', $conjunto->id)
            ->where('is_active', true)
            ->where('account_type', 'asset')
            ->where(function ($query) {
                $query->where('code', 'like', '1105%')  // Cash accounts
                    ->orWhere('code', 'like', '1110%'); // Bank accounts
            })
            ->orderBy('code')
            ->get();

        $paymentMethods = PaymentMethodAccountMapping::getAvailablePaymentMethods();

        return Inertia::render('Payments/MethodAccountMappings/Edit', [
            'mapping' => $paymentMethodAccountMapping,
            'cashAccounts' => $cashAccounts,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function update(Request $request, PaymentMethodAccountMapping $paymentMethodAccountMapping)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'payment_method' => [
                'required',
                Rule::in(array_keys(PaymentMethodAccountMapping::getAvailablePaymentMethods())),
                Rule::unique('payment_method_account_mappings')
                    ->where('conjunto_config_id', $conjunto->id)
                    ->where('is_active', true)
                    ->ignore($paymentMethodAccountMapping->id),
            ],
            'cash_account_id' => 'required|exists:chart_of_accounts,id',
            'is_active' => 'boolean',
        ]);

        $paymentMethodAccountMapping->update([
            'payment_method' => $validated['payment_method'],
            'cash_account_id' => $validated['cash_account_id'],
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('payment-method-account-mappings.show', $paymentMethodAccountMapping)
            ->with('success', 'Mapeo de cuenta actualizado exitosamente.');
    }

    public function destroy(PaymentMethodAccountMapping $paymentMethodAccountMapping)
    {
        // Check if this mapping is being used in any accounting transactions
        // We would need to check for related payment records that might reference this mapping
        // For now, we'll allow deletion but in production you might want to add additional checks

        $paymentMethodAccountMapping->delete();

        return redirect()->route('payment-method-account-mappings.index')
            ->with('success', 'Mapeo de cuenta eliminado exitosamente.');
    }

    public function createDefaults()
    {
        try {
            $conjunto = ConjuntoConfig::where('is_active', true)->first();

            if (! $conjunto) {
                return back()->withErrors([
                    'create_defaults' => 'No se encontró configuración activa del conjunto.',
                ]);
            }

            // Check if chart of accounts exists
            if (! ChartOfAccounts::where('conjunto_config_id', $conjunto->id)->exists()) {
                return back()->withErrors([
                    'create_defaults' => 'Debe crear el plan de cuentas antes de configurar mapeos de métodos de pago.',
                ]);
            }

            // Define default payment method mappings
            $defaultMappings = [
                'cash' => '110501', // Caja General
                'bank_transfer' => '111001', // Banco - Cuenta Corriente
                'credit_card' => '111001', // Banco - Cuenta Corriente
                'debit_card' => '111001', // Banco - Cuenta Corriente
                'check' => '111001', // Banco - Cuenta Corriente
            ];

            $createdCount = 0;
            $skippedCount = 0;

            foreach ($defaultMappings as $paymentMethod => $accountCode) {
                // Check if mapping already exists
                $existingMapping = PaymentMethodAccountMapping::where('conjunto_config_id', $conjunto->id)
                    ->where('payment_method', $paymentMethod)
                    ->first();

                if ($existingMapping) {
                    $skippedCount++;

                    continue;
                }

                // Find the account
                $account = ChartOfAccounts::where('conjunto_config_id', $conjunto->id)
                    ->where('code', $accountCode)
                    ->first();

                if ($account) {
                    PaymentMethodAccountMapping::create([
                        'conjunto_config_id' => $conjunto->id,
                        'payment_method' => $paymentMethod,
                        'cash_account_id' => $account->id,
                        'is_active' => true,
                    ]);
                    $createdCount++;
                }
            }

            $message = "Mapeos por defecto procesados: {$createdCount} creados";
            if ($skippedCount > 0) {
                $message .= ", {$skippedCount} ya existían";
            }

            return back()->with('success', "{$message}.");

        } catch (\Exception $e) {
            return back()->withErrors([
                'create_defaults' => 'Error al crear mapeos por defecto: '.$e->getMessage(),
            ]);
        }
    }

    public function toggle(PaymentMethodAccountMapping $paymentMethodAccountMapping)
    {
        $paymentMethodAccountMapping->update([
            'is_active' => ! $paymentMethodAccountMapping->is_active,
        ]);

        $status = $paymentMethodAccountMapping->is_active ? 'activado' : 'desactivado';

        return back()->with('success', "Mapeo de cuenta {$status} exitosamente.");
    }
}
