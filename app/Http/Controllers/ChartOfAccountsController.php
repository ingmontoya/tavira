<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ChartOfAccountsController extends Controller
{
    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $query = ChartOfAccounts::forConjunto($conjunto->id)
            ->with(['parent', 'children']);

        if ($request->filled('account_type')) {
            $query->byType($request->account_type);
        }

        if ($request->filled('level')) {
            $query->byLevel($request->level);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        if ($request->filled('postable_only')) {
            $query->postable();
        }

        $accounts = $query->active()
            ->orderBy('code')
            ->paginate(50)
            ->withQueryString();

        $hasAccounts = ChartOfAccounts::forConjunto($conjunto->id)->exists();
        $accountsCount = ChartOfAccounts::forConjunto($conjunto->id)->count();
        $needsSync = $accountsCount > 0 && $accountsCount < 316;

        return Inertia::render('Accounting/ChartOfAccounts/Index', [
            'accounts' => $accounts,
            'filters' => $request->only(['account_type', 'level', 'search', 'parent_id', 'postable_only']),
            'accountTypes' => [
                'asset' => 'Activo',
                'liability' => 'Pasivo',
                'equity' => 'Patrimonio',
                'income' => 'Ingresos',
                'expense' => 'Gastos',
            ],
            // 'hierarchicalTree' => ChartOfAccounts::buildHierarchicalTree($conjunto->id), // Commented out for performance - generates 350+ queries
            'has_accounts' => $hasAccounts,
            'accounts_count' => $accountsCount,
            'needs_sync' => $needsSync,
        ]);
    }

    public function create(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $parentAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->active()
            ->where('level', '<', 4)
            ->orderBy('code')
            ->get();

        $parent = null;
        if ($request->filled('parent_id')) {
            $parent = ChartOfAccounts::forConjunto($conjunto->id)
                ->findOrFail($request->parent_id);
        }

        return Inertia::render('Accounting/ChartOfAccounts/Create', [
            'parentAccounts' => $parentAccounts,
            'parent' => $parent,
            'accountTypes' => [
                'asset' => 'Activo',
                'liability' => 'Pasivo',
                'equity' => 'Patrimonio',
                'income' => 'Ingresos',
                'expense' => 'Gastos',
            ],
            'natures' => [
                'debit' => 'Débito',
                'credit' => 'Crédito',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('chart_of_accounts')->where('conjunto_config_id', $conjunto->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'account_type' => 'required|in:asset,liability,equity,income,expense',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'level' => 'required|integer|min:1|max:4',
            'requires_third_party' => 'boolean',
            'nature' => 'required|in:debit,credit',
            'accepts_posting' => 'boolean',
        ]);

        $validated['conjunto_config_id'] = $conjunto->id;
        $validated['is_active'] = true;

        try {
            $account = ChartOfAccounts::create($validated);

            return redirect()
                ->route('accounting.chart-of-accounts.show', $account)
                ->with('success', 'Cuenta creada exitosamente.');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['code' => $e->getMessage()]);
        }
    }

    public function show(ChartOfAccounts $chartOfAccount)
    {
        $chartOfAccount->load(['parent', 'children']);

        $balance = $chartOfAccount->getBalance();

        // Get recent transactions for this account
        $recentTransactions = $chartOfAccount->transactionEntries()
            ->with(['transaction' => function ($query) {
                $query->where('status', 'contabilizado')
                    ->orderBy('transaction_date', 'desc');
            }])
            ->whereHas('transaction', function ($query) {
                $query->where('status', 'contabilizado');
            })
            ->orderByDesc('id')
            ->limit(10)
            ->get()
            ->map(function ($entry) {
                $transaction = $entry->transaction;

                // Get reference number based on the reference model
                $referenceNumber = $transaction->transaction_number;

                // Only try to load reference for known types to avoid class not found errors
                $validReferenceTypes = ['expense', 'invoice', 'expense_payment', 'payment_application'];

                if ($transaction->reference_type && in_array($transaction->reference_type, $validReferenceTypes)) {
                    try {
                        $reference = $transaction->reference;
                        if ($reference) {
                            if ($reference instanceof \App\Models\Expense) {
                                $referenceNumber = $reference->expense_number;
                            } elseif ($reference instanceof \App\Models\Invoice) {
                                $referenceNumber = $reference->invoice_number;
                            } elseif (method_exists($reference, 'getReferenceNumber')) {
                                $referenceNumber = $reference->getReferenceNumber();
                            }
                        }
                    } catch (\Exception $e) {
                        // If reference fails to load, use transaction_number
                        $referenceNumber = $transaction->transaction_number;
                    }
                }

                return [
                    'id' => $transaction->id,
                    'reference' => $referenceNumber,
                    'description' => $transaction->description,
                    'amount' => $entry->debit_amount > 0 ? $entry->debit_amount : $entry->credit_amount,
                    'type' => $entry->debit_amount > 0 ? 'debit' : 'credit',
                    'transaction_date' => $transaction->transaction_date->toDateString(),
                    'created_at' => $entry->created_at->toISOString(),
                ];
            });

        // Get monthly balance trend (last 6 months)
        $monthlyBalance = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startDate = $date->copy()->startOfMonth()->toDateString();
            $endDate = $date->copy()->endOfMonth()->toDateString();

            $monthBalance = $chartOfAccount->getBalance($startDate, $endDate);

            // Calculate change percentage
            $prevMonth = $i < 5 ? $monthlyBalance[count($monthlyBalance) - 1]['balance'] ?? 0 : 0;
            $change = $prevMonth != 0 ? (($monthBalance - $prevMonth) / abs($prevMonth)) * 100 : 0;

            $monthlyBalance[] = [
                'month' => $date->translatedFormat('M Y'),
                'balance' => $monthBalance,
                'change' => round($change, 1),
            ];
        }

        return Inertia::render('Accounting/ChartOfAccounts/Show', [
            'account' => $chartOfAccount->append('balance'),
            'recentTransactions' => $recentTransactions->toArray(),
            'monthlyBalance' => $monthlyBalance,
            'canEdit' => ! $chartOfAccount->transactionEntries()->exists(),
        ]);
    }

    public function edit(ChartOfAccounts $chartOfAccount)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        if ($chartOfAccount->transactionEntries()->exists()) {
            return back()->withErrors(['account' => 'No se puede editar una cuenta que tiene movimientos contables.']);
        }

        $parentAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->active()
            ->where('id', '!=', $chartOfAccount->id)
            ->where('level', '<', 4)
            ->orderBy('code')
            ->get();

        return Inertia::render('Accounting/ChartOfAccounts/Edit', [
            'account' => $chartOfAccount,
            'parentAccounts' => $parentAccounts,
            'accountTypes' => [
                'asset' => 'Activo',
                'liability' => 'Pasivo',
                'equity' => 'Patrimonio',
                'income' => 'Ingresos',
                'expense' => 'Gastos',
            ],
            'natures' => [
                'debit' => 'Débito',
                'credit' => 'Crédito',
            ],
        ]);
    }

    public function update(Request $request, ChartOfAccounts $chartOfAccount)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        if ($chartOfAccount->transactionEntries()->exists()) {
            return back()->withErrors(['account' => 'No se puede editar una cuenta que tiene movimientos contables.']);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique('chart_of_accounts')
                    ->where('conjunto_config_id', $conjunto->id)
                    ->ignore($chartOfAccount->id),
            ],
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'account_type' => 'required|in:asset,liability,equity,income,expense',
            'parent_id' => 'nullable|exists:chart_of_accounts,id',
            'level' => 'required|integer|min:1|max:4',
            'requires_third_party' => 'boolean',
            'nature' => 'required|in:debit,credit',
            'accepts_posting' => 'boolean',
            'is_active' => 'boolean',
        ]);

        try {
            $chartOfAccount->update($validated);

            return redirect()
                ->route('accounting.chart-of-accounts.show', $chartOfAccount)
                ->with('success', 'Cuenta actualizada exitosamente.');
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['code' => $e->getMessage()]);
        }
    }

    public function destroy(ChartOfAccounts $chartOfAccount)
    {
        if ($chartOfAccount->transactionEntries()->exists()) {
            return back()->withErrors(['account' => 'No se puede eliminar una cuenta que tiene movimientos contables.']);
        }

        if ($chartOfAccount->children()->exists()) {
            return back()->withErrors(['account' => 'No se puede eliminar una cuenta que tiene cuentas hijas.']);
        }

        $chartOfAccount->delete();

        return redirect()
            ->route('accounting.chart-of-accounts.index')
            ->with('success', 'Cuenta eliminada exitosamente.');
    }

    public function getByType(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $request->validate([
            'type' => 'required|in:asset,liability,equity,income,expense',
            'postable_only' => 'boolean',
        ]);

        $query = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType($request->type)
            ->active()
            ->orderBy('code');

        if ($request->postable_only) {
            $query->postable();
        }

        return response()->json([
            'accounts' => $query->get(),
        ]);
    }

    public function getHierarchical()
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        return response()->json([
            'tree' => ChartOfAccounts::buildHierarchicalTree($conjunto->id),
        ]);
    }

    public function getBalance(ChartOfAccounts $chartOfAccount, Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $balance = $chartOfAccount->getBalance(
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'balance' => $balance,
            'account' => $chartOfAccount->full_name,
        ]);
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

            // Check if accounts already exist
            $existingAccounts = ChartOfAccounts::forConjunto($conjunto->id)->count();

            if ($existingAccounts > 0) {
                return back()->withErrors([
                    'create_defaults' => 'Ya existen cuentas configuradas para este conjunto.',
                ]);
            }

            // Create default chart of accounts
            $seeder = new \Database\Seeders\ChartOfAccountsSeeder;
            $seeder->run();

            $accountsCreated = ChartOfAccounts::forConjunto($conjunto->id)->count();

            return back()->with('success', "Plan de cuentas inicializado exitosamente. Se crearon {$accountsCreated} cuentas contables.");

        } catch (\Exception $e) {
            return back()->withErrors([
                'create_defaults' => 'Error al crear el plan de cuentas: '.$e->getMessage(),
            ]);
        }
    }

    public function sync()
    {
        try {
            $conjunto = ConjuntoConfig::where('is_active', true)->first();

            if (! $conjunto) {
                return back()->withErrors([
                    'sync' => 'No se encontró configuración activa del conjunto.',
                ]);
            }

            $beforeCount = ChartOfAccounts::forConjunto($conjunto->id)->count();

            // Run seeder with reflection to call private method
            $seeder = new \Database\Seeders\ChartOfAccountsSeeder;
            $reflection = new \ReflectionClass($seeder);
            $method = $reflection->getMethod('seedAccountsForConjunto');
            $method->setAccessible(true);
            $method->invoke($seeder, $conjunto->id);

            $afterCount = ChartOfAccounts::forConjunto($conjunto->id)->count();
            $newAccounts = $afterCount - $beforeCount;

            if ($newAccounts > 0) {
                return back()->with('success', "Plan de cuentas sincronizado exitosamente. Se agregaron {$newAccounts} cuentas nuevas. Total: {$afterCount} cuentas.");
            } else {
                return back()->with('success', "Plan de cuentas sincronizado exitosamente. Total: {$afterCount} cuentas.");
            }

        } catch (\Exception $e) {
            return back()->withErrors([
                'sync' => 'Error al sincronizar el plan de cuentas: '.$e->getMessage(),
            ]);
        }
    }
}
