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

        return Inertia::render('Accounting/Budgets/Create', [
            'incomeAccounts' => $incomeAccounts,
            'expenseAccounts' => $expenseAccounts,
            'previousBudget' => $previousBudget,
            'suggestedYear' => now()->year + 1,
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
            'items' => 'required|array|min:1',
            'items.*.account_id' => 'required|exists:chart_of_accounts,id',
            'items.*.category' => 'required|in:income,expense',
            'items.*.budgeted_amount' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:500',
            'items.*.monthly_distribution' => 'nullable|array',
            'copy_from_previous' => 'boolean',
            'previous_year' => 'nullable|integer|exists:budgets,fiscal_year',
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
            } else {
                $budget = Budget::create([
                    'conjunto_config_id' => $conjunto->id,
                    'name' => $validated['name'],
                    'fiscal_year' => $validated['fiscal_year'],
                    'start_date' => $validated['start_date'],
                    'end_date' => $validated['end_date'],
                    'status' => 'draft',
                ]);

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
            }
        });

        return redirect()
            ->route('budgets.index')
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

        return Inertia::render('Accounting/Budgets/Show', [
            'budget' => $budget,
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
        if ($budget->status !== 'draft') {
            return back()->withErrors(['budget' => 'Solo se pueden editar presupuestos en borrador.']);
        }

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
        if ($budget->status !== 'draft') {
            return back()->withErrors(['budget' => 'Solo se pueden editar presupuestos en borrador.']);
        }

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
            ->route('budgets.index')
            ->with('success', 'Presupuesto eliminado exitosamente.');
    }

    public function approve(Budget $budget)
    {
        try {
            $budget->approve();

            return back()->with('success', 'Presupuesto aprobado exitosamente.');
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
}
