<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetItem;
use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $query = Budget::forConjunto($conjunto->id)
            ->with(['approvedBy']);

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('year')) {
            $query->byYear($request->year);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $budgets = $query->orderBy('fiscal_year', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Accounting/Budgets/Index', [
            'budgets' => $budgets,
            'filters' => $request->only(['status', 'year', 'search']),
            'statuses' => [
                'draft' => 'Borrador',
                'approved' => 'Aprobado',
                'active' => 'Activo',
                'closed' => 'Cerrado',
            ],
            'availableYears' => $this->getAvailableYears($conjunto->id),
        ]);
    }

    public function create(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $incomeAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType('income')
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        $expenseAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType('expense')
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        $previousYear = $request->get('previous_year');
        $previousBudget = null;

        if ($previousYear) {
            $previousBudget = Budget::forConjunto($conjunto->id)
                ->byYear($previousYear)
                ->with('items.account')
                ->first();
        }

        // Get available years for copying from previous
        $availablePreviousYears = Budget::forConjunto($conjunto->id)
            ->selectRaw('DISTINCT fiscal_year')
            ->orderBy('fiscal_year', 'desc')
            ->pluck('fiscal_year')
            ->toArray();

        return Inertia::render('Accounting/Budgets/Create', [
            'incomeAccounts' => $incomeAccounts,
            'expenseAccounts' => $expenseAccounts,
            'previousBudget' => $previousBudget,
            'suggestedYear' => now()->year + 1,
            'availablePreviousYears' => $availablePreviousYears,
        ]);
    }

    public function store(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'fiscal_year' => 'required|integer|min:2020|max:2050|unique:budgets,fiscal_year,NULL,id,conjunto_config_id,'.$conjunto->id,
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'items' => 'nullable|array',
            'items.*.account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.category' => 'required|in:income,expense',
            'items.*.budgeted_amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
            'items.*.monthly_distribution' => 'nullable|array',
            'copy_from_previous' => 'boolean',
            'previous_year' => 'nullable|integer|exists:budgets,fiscal_year',
            'use_default_template' => 'boolean',
        ]);

        DB::transaction(function () use ($validated, $conjunto) {
            if ($validated['copy_from_previous'] && $validated['previous_year']) {
                $budget = Budget::createFromPrevious(
                    $conjunto->id,
                    $validated['previous_year'],
                    $validated['fiscal_year']
                );

                $budget->update([
                    'name' => $validated['name'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                ]);
            } elseif ($validated['use_default_template'] ?? false) {
                $budget = Budget::createWithDefaultTemplate(
                    $conjunto->id,
                    $validated['name'],
                    $validated['fiscal_year'],
                    $validated['start_date'],
                    $validated['end_date']
                );
            } else {
                $budget = Budget::create([
                    'conjunto_config_id' => $conjunto->id,
                    'name' => $validated['name'],
                    'fiscal_year' => $validated['fiscal_year'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'status' => 'draft',
                ]);

                // Only process items if they were provided
                if (! empty($validated['items'])) {
                    foreach ($validated['items'] as $itemData) {
                        $item = $budget->items()->create([
                            'account_id' => $itemData['account_id'],
                            'category' => $itemData['category'],
                            'budgeted_amount' => $itemData['budgeted_amount'],
                            'notes' => $itemData['notes'] ?? null,
                        ]);

                        if (isset($itemData['monthly_distribution'])) {
                            $this->updateMonthlyDistribution($item, $itemData['monthly_distribution']);
                        } else {
                            $item->distributeEqually();
                        }
                    }
                }

                $budget->calculateTotals();
            }
        });

        return redirect()
            ->route('accounting.budgets.index')
            ->with('success', 'Presupuesto creado exitosamente.');
    }

    public function show(Budget $budget)
    {
        $budget->load([
            'items.account',
            'items.executions',
            'approvedBy',
        ]);

        $currentMonth = now()->month;
        $currentYear = $budget->fiscal_year;

        $executionSummary = $budget->getExecutionSummary($currentMonth, $currentYear);

        // Transform budget data to match frontend expectations
        $transformedBudget = $this->transformBudgetForFrontend($budget, $executionSummary);

        return Inertia::render('Accounting/Budgets/Show', [
            'budget' => $transformedBudget,
            'executionSummary' => $executionSummary,
            'currentPeriod' => [
                'month' => $currentMonth,
                'year' => $currentYear,
                'name' => now()->translatedFormat('F Y'),
            ],
        ]);
    }

    public function edit(Budget $budget)
    {

        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $incomeAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType('income')
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        $expenseAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType('expense')
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        $budget->load('items.account');

        return Inertia::render('Accounting/Budgets/Edit', [
            'budget' => $budget,
            'incomeAccounts' => $incomeAccounts,
            'expenseAccounts' => $expenseAccounts,
        ]);
    }

    public function update(Request $request, Budget $budget)
    {

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'items' => 'required|array|min:1',
            'items.*.account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.category' => 'required|in:income,expense',
            'items.*.budgeted_amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
            'items.*.monthly_distribution' => 'nullable|array',
        ]);

        DB::transaction(function () use ($validated, $budget) {
            $budget->update([
                'name' => $validated['name'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
            ]);

            // Delete existing items
            $budget->items()->delete();

            // Create new items
            foreach ($validated['items'] as $itemData) {
                $item = $budget->items()->create([
                    'account_id' => $itemData['account_id'],
                    'category' => $itemData['category'],
                    'budgeted_amount' => $itemData['budgeted_amount'],
                    'notes' => $itemData['notes'] ?? null,
                ]);

                if (isset($itemData['monthly_distribution'])) {
                    $this->updateMonthlyDistribution($item, $itemData['monthly_distribution']);
                } else {
                    $item->distributeEqually();
                }
            }

            $budget->calculateTotals();
        });

        return redirect()
            ->route('budgets.show', $budget)
            ->with('success', 'Presupuesto actualizado exitosamente.');
    }

    public function destroy(Budget $budget)
    {
        if ($budget->status === 'active') {
            return back()->withErrors(['budget' => 'No se pueden eliminar presupuestos activos.']);
        }

        $budget->items()->delete();
        $budget->delete();

        return redirect()
            ->route('accounting.budgets.index')
            ->with('success', 'Presupuesto eliminado exitosamente.');
    }

    public function approve(Budget $budget)
    {
        // Additional permission check at controller level
        if (! auth()->user()->can('approve_budgets')) {
            abort(403, 'No tiene permisos para aprobar presupuestos');
        }

        try {
            $budget->approve();

            return back()->with('success', 'Presupuesto aprobado exitosamente por el Concejo de Administración.');
        } catch (\Exception $e) {
            return back()->withErrors(['budget' => $e->getMessage()]);
        }
    }

    public function activate(Budget $budget)
    {
        try {
            $budget->activate();

            return back()->with('success', 'Presupuesto activado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['budget' => $e->getMessage()]);
        }
    }

    public function close(Budget $budget)
    {
        try {
            $budget->close();

            return back()->with('success', 'Presupuesto cerrado exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['budget' => $e->getMessage()]);
        }
    }

    public function execution(Request $request, Budget $budget)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', $budget->fiscal_year);

        $executionSummary = $budget->getExecutionSummary($month, $year);

        return Inertia::render('Accounting/Budgets/Execution', [
            'budget' => $budget,
            'executionSummary' => $executionSummary,
            'currentPeriod' => [
                'month' => $month,
                'year' => $year,
            ],
            'availableMonths' => $this->getAvailableMonths(),
        ]);
    }

    public function updateMonthlyDistribution(BudgetItem $item, array $distribution)
    {
        $monthColumns = [
            1 => 'jan_amount',
            2 => 'feb_amount',
            3 => 'mar_amount',
            4 => 'apr_amount',
            5 => 'may_amount',
            6 => 'jun_amount',
            7 => 'jul_amount',
            8 => 'aug_amount',
            9 => 'sep_amount',
            10 => 'oct_amount',
            11 => 'nov_amount',
            12 => 'dec_amount',
        ];

        $updateData = [];
        foreach ($distribution as $month => $amount) {
            if (isset($monthColumns[$month])) {
                $updateData[$monthColumns[$month]] = $amount;
            }
        }

        $item->update($updateData);
    }

    public function copyFromPrevious(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'previous_year' => 'required|integer|exists:budgets,fiscal_year',
            'new_year' => 'required|integer|min:2020|max:2050|unique:budgets,fiscal_year,NULL,id,conjunto_config_id,'.$conjunto->id,
        ]);

        try {
            $budget = Budget::createFromPrevious(
                $conjunto->id,
                $validated['previous_year'],
                $validated['new_year']
            );

            return redirect()
                ->route('budgets.edit', $budget)
                ->with('success', 'Presupuesto copiado exitosamente. Puede realizar ajustes antes de guardarlo.');
        } catch (\Exception $e) {
            return back()->withErrors(['copy' => $e->getMessage()]);
        }
    }

    public function createWithTemplate(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'fiscal_year' => 'required|integer|min:2020|max:2050|unique:budgets,fiscal_year,NULL,id,conjunto_config_id,'.$conjunto->id,
        ]);

        try {
            $startDate = $validated['fiscal_year'].'-01-01';
            $endDate = $validated['fiscal_year'].'-12-31';

            $budget = Budget::createWithDefaultTemplate(
                $conjunto->id,
                $validated['name'],
                $validated['fiscal_year'],
                $startDate,
                $endDate
            );

            return redirect()
                ->route('accounting.budgets.edit', $budget)
                ->with('success', 'Presupuesto creado con plantilla estándar. Configure los montos presupuestados.');
        } catch (\Exception $e) {
            return back()->withErrors(['template' => $e->getMessage()]);
        }
    }

    public function addDefaultItems(Budget $budget)
    {
        if ($budget->status === 'active') {
            return back()->withErrors(['budget' => 'No se pueden modificar presupuestos activos.']);
        }

        try {
            $budget->createDefaultBudgetItems();

            return back()->with('success', 'Partidas presupuestales estándar agregadas exitosamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['budget' => 'Error al agregar partidas: '.$e->getMessage()]);
        }
    }

    private function getAvailableYears(int $conjuntoConfigId): array
    {
        $years = Budget::forConjunto($conjuntoConfigId)
            ->selectRaw('DISTINCT fiscal_year')
            ->orderBy('fiscal_year', 'desc')
            ->pluck('fiscal_year')
            ->toArray();

        // Add current and next year if not present
        $currentYear = now()->year;
        if (! in_array($currentYear, $years)) {
            $years[] = $currentYear;
        }
        if (! in_array($currentYear + 1, $years)) {
            $years[] = $currentYear + 1;
        }

        rsort($years);

        return $years;
    }

    private function getAvailableMonths(): array
    {
        return [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];
    }

    public function alerts(Request $request, Budget $budget)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', $budget->fiscal_year);

        $alerts = $budget->getBudgetAlerts((int) $month, (int) $year);
        $alertsCount = $budget->getAlertsCount((int) $month, (int) $year);

        return response()->json([
            'alerts' => $alerts,
            'count' => $alertsCount,
            'has_active_alerts' => $budget->hasActiveAlerts((int) $month, (int) $year),
            'period' => [
                'month' => $month,
                'year' => $year,
                'name' => $this->getAvailableMonths()[$month].' '.$year,
            ],
        ]);
    }

    public function refreshExecution(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        BudgetExecution::refreshAllExecutionsForPeriod($conjunto->id, (int) $month, (int) $year);

        return response()->json([
            'message' => 'Ejecución presupuestal actualizada exitosamente.',
            'period' => [
                'month' => $month,
                'year' => $year,
                'name' => $this->getAvailableMonths()[$month].' '.$year,
            ],
        ]);
    }

    public function createItem(Budget $budget)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $incomeAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType('income')
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        $expenseAccounts = ChartOfAccounts::forConjunto($conjunto->id)
            ->byType('expense')
            ->postable()
            ->active()
            ->orderBy('code')
            ->get();

        // Get already used accounts to avoid duplicates
        $usedAccountIds = $budget->items()->pluck('account_id')->toArray();

        return Inertia::render('Accounting/Budgets/Items/Create', [
            'budget' => $budget,
            'incomeAccounts' => $incomeAccounts,
            'expenseAccounts' => $expenseAccounts,
            'usedAccountIds' => $usedAccountIds,
        ]);
    }

    public function storeItem(Request $request, Budget $budget)
    {
        if ($budget->status === 'active') {
            return back()->withErrors(['budget' => 'No se pueden agregar partidas a presupuestos activos.']);
        }

        $validated = $request->validate([
            'account_id' => 'required|exists:chart_of_accounts,id',
            'category' => 'required|in:income,expense',
            'budgeted_amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'monthly_distribution' => 'nullable|array',
            'monthly_distribution.*' => 'nullable|numeric|min:0',
        ]);

        // Check if account is already used in this budget
        if ($budget->items()->where('account_id', $validated['account_id'])->exists()) {
            return back()->withErrors(['account_id' => 'Esta cuenta ya está incluida en el presupuesto.']);
        }

        DB::transaction(function () use ($validated, $budget) {
            $item = $budget->items()->create([
                'account_id' => $validated['account_id'],
                'category' => $validated['category'],
                'budgeted_amount' => $validated['budgeted_amount'],
                'notes' => $validated['notes'] ?? null,
            ]);

            if (isset($validated['monthly_distribution'])) {
                $this->updateMonthlyDistribution($item, $validated['monthly_distribution']);
            } else {
                $item->distributeEqually();
            }

            $budget->calculateTotals();
        });

        return redirect()
            ->route('accounting.budgets.show', $budget)
            ->with('success', 'Partida presupuestal agregada exitosamente.');
    }

    private function transformBudgetForFrontend(Budget $budget, array $executionSummary): array
    {
        // Calculate totals
        $totalBudget = $budget->total_budgeted_income + $budget->total_budgeted_expenses;
        $totalExecuted = $executionSummary['total_income_actual'] + $executionSummary['total_expenses_actual'];
        $executionPercentage = $totalBudget > 0 ? round(($totalExecuted / $totalBudget) * 100, 2) : 0;

        // Map status to frontend format
        $statusMapping = [
            'draft' => 'Draft',
            'approved' => 'Active', // Map approved to Active for frontend compatibility
            'active' => 'Active',
            'closed' => 'Closed',
        ];

        // Transform budget items with execution data
        $transformedItems = $budget->items->map(function ($item) use ($executionSummary) {
            // Find execution data for this item from the summary
            $itemExecutions = collect([
                ...$executionSummary['income']->where('budgetItem.id', $item->id),
                ...$executionSummary['expenses']->where('budgetItem.id', $item->id),
            ]);

            $executedAmount = $itemExecutions->sum('actual_amount');
            $varianceAmount = $executedAmount - $item->budgeted_amount;
            $variancePercentage = $item->budgeted_amount > 0
                ? round(($varianceAmount / $item->budgeted_amount) * 100, 2)
                : 0;

            return [
                'id' => $item->id,
                'account' => [
                    'id' => $item->account->id,
                    'code' => $item->account->code,
                    'name' => $item->account->name,
                    'account_type' => $item->account->account_type,
                ],
                'budgeted_amount' => (float) $item->budgeted_amount,
                'executed_amount' => (float) $executedAmount,
                'variance_amount' => (float) $varianceAmount,
                'variance_percentage' => (float) $variancePercentage,
                'description' => $item->notes,
            ];
        });

        return [
            'id' => $budget->id,
            'name' => $budget->name,
            'description' => null, // Add if you have a description field
            'year' => $budget->fiscal_year,
            'start_date' => $budget->start_date->toDateString(),
            'end_date' => $budget->end_date->toDateString(),
            'total_budget' => (float) $totalBudget,
            'total_executed' => (float) $totalExecuted,
            'status' => $statusMapping[$budget->status] ?? 'Draft',
            'approval_date' => $budget->approved_at?->toDateString(),
            'execution_percentage' => (float) $executionPercentage,
            'items' => $transformedItems->toArray(),
            'created_at' => $budget->created_at->toISOString(),
            'updated_at' => $budget->updated_at->toISOString(),
        ];
    }
}
