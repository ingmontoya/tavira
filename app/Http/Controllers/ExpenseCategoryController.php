<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\ExpenseCategory;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseCategoryController extends Controller
{
    public function index()
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $categories = ExpenseCategory::forConjunto($conjunto->id)
            ->with(['defaultDebitAccount', 'defaultCreditAccount', 'budgetAccount'])
            ->withCount('expenses')
            ->orderBy('name')
            ->get();

        return Inertia::render('Expenses/Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function show(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->load([
            'defaultDebitAccount',
            'defaultCreditAccount',
            'budgetAccount',
            'expenses' => function ($query) {
                $query->with(['createdBy', 'approvedBy'])
                    ->orderBy('expense_date', 'desc')
                    ->limit(10);
            },
        ]);

        // Get expense summary for current year
        $currentYear = now()->year;
        $monthlyExpenses = $expenseCategory->getMonthlyExpenses($currentYear);

        return Inertia::render('Expenses/Categories/Show', [
            'category' => $expenseCategory,
            'monthlyExpenses' => $monthlyExpenses,
            'currentYear' => $currentYear,
        ]);
    }

    public function create()
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $expenseAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType('expense')
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        $assetAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType('asset')
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        $liabilityAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType('liability')
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        return Inertia::render('Expenses/Categories/Create', [
            'expenseAccounts' => $expenseAccounts,
            'assetAccounts' => $assetAccounts,
            'liabilityAccounts' => $liabilityAccounts,
        ]);
    }

    public function store(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,NULL,id,conjunto_config_id,'.$conjunto->id,
            'description' => 'nullable|string|max:1000',
            'default_debit_account_id' => 'nullable|exists:chart_of_accounts,id',
            'default_credit_account_id' => 'nullable|exists:chart_of_accounts,id',
            'budget_account_id' => 'nullable|exists:chart_of_accounts,id',
            'color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6})$/',
            'icon' => 'nullable|string|max:50',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['conjunto_config_id'] = $conjunto->id;
        $validated['requires_approval'] = $request->boolean('requires_approval', false);
        $validated['is_active'] = $request->boolean('is_active', true);

        $category = ExpenseCategory::create($validated);

        return redirect()->route('expense-categories.show', $category)
            ->with('success', 'Categoría de gasto creada exitosamente');
    }

    public function edit(ExpenseCategory $expenseCategory)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $expenseAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType('expense')
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        $assetAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType('asset')
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        $liabilityAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType('liability')
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        return Inertia::render('Expenses/Categories/Edit', [
            'category' => $expenseCategory,
            'expenseAccounts' => $expenseAccounts,
            'assetAccounts' => $assetAccounts,
            'liabilityAccounts' => $liabilityAccounts,
        ]);
    }

    public function update(Request $request, ExpenseCategory $expenseCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:expense_categories,name,'.$expenseCategory->id.',id,conjunto_config_id,'.$expenseCategory->conjunto_config_id,
            'description' => 'nullable|string|max:1000',
            'default_debit_account_id' => 'nullable|exists:chart_of_accounts,id',
            'default_credit_account_id' => 'nullable|exists:chart_of_accounts,id',
            'budget_account_id' => 'nullable|exists:chart_of_accounts,id',
            'color' => 'nullable|string|regex:/^#([A-Fa-f0-9]{6})$/',
            'icon' => 'nullable|string|max:50',
            'requires_approval' => 'boolean',
            'is_active' => 'boolean',
        ]);

        $validated['requires_approval'] = $request->boolean('requires_approval', false);
        $validated['is_active'] = $request->boolean('is_active', true);

        $expenseCategory->update($validated);

        return redirect()->route('expense-categories.show', $expenseCategory)
            ->with('success', 'Categoría de gasto actualizada exitosamente');
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        if (! $expenseCategory->canBeDeleted()) {
            return redirect()->route('expense-categories.index')
                ->with('error', 'No se puede eliminar una categoría que tiene gastos asociados');
        }

        $expenseCategory->delete();

        return redirect()->route('expense-categories.index')
            ->with('success', 'Categoría de gasto eliminada exitosamente');
    }
}
