<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccounts;
use App\Models\ConjuntoConfig;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Supplier;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseController extends Controller
{
    protected ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    public function index(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $query = Expense::with(['expenseCategory', 'supplier', 'debitAccount', 'creditAccount', 'createdBy', 'approvedBy']);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category_id')) {
            $query->where('expense_category_id', $request->category_id);
        }

        if ($request->filled('date_from')) {
            $query->where('expense_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('expense_date', '<=', $request->date_to);
        }

        if ($request->filled('vendor')) {
            $query->where('vendor_name', 'like', '%'.$request->vendor.'%');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('expense_number', 'like', "%{$search}%")
                    ->orWhere('vendor_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $expenses = $query->forConjunto($conjunto->id)
            ->orderBy('expense_date', 'desc')
            ->paginate(25);

        $categories = ExpenseCategory::forConjunto($conjunto->id)
            ->active()
            ->orderBy('name')
            ->get();

        // Summary stats
        $stats = [
            'total_pending' => Expense::forConjunto($conjunto->id)->pending()->sum('total_amount'),
            'total_approved' => Expense::forConjunto($conjunto->id)->approved()->sum('total_amount'),
            'total_paid' => Expense::forConjunto($conjunto->id)->paid()->sum('total_amount'),
            'overdue_count' => Expense::forConjunto($conjunto->id)->overdue()->count(),
        ];

        return Inertia::render('Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => $request->only(['status', 'category_id', 'date_from', 'date_to', 'vendor', 'search']),
        ]);
    }

    public function show(Expense $expense)
    {
        $expense->load([
            'expenseCategory',
            'supplier',
            'debitAccount',
            'creditAccount',
            'createdBy',
            'approvedBy',
            'accountingTransactions.entries.account',
        ]);

        return Inertia::render('Expenses/Show', [
            'expense' => $expense,
        ]);
    }

    public function create()
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $categories = ExpenseCategory::forConjunto($conjunto->id)
            ->active()
            ->with(['defaultDebitAccount', 'defaultCreditAccount'])
            ->orderBy('name')
            ->get();

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

        $suppliers = Supplier::forConjunto($conjunto->id)
            ->active()
            ->orderBy('name')
            ->get();

        return Inertia::render('Expenses/Create', [
            'categories' => $categories,
            'expenseAccounts' => $expenseAccounts,
            'assetAccounts' => $assetAccounts,
            'liabilityAccounts' => $liabilityAccounts,
            'suppliers' => $suppliers,
        ]);
    }

    public function store(Request $request)
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_document' => 'nullable|string|max:50',
            'vendor_email' => 'nullable|email|max:255',
            'vendor_phone' => 'nullable|string|max:20',
            'description' => 'required|string|max:500',
            'expense_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:expense_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'debit_account_id' => 'required|exists:chart_of_accounts,id',
            'credit_account_id' => 'required|exists:chart_of_accounts,id',
            'notes' => 'nullable|string|max:1000',
            'submit_for_approval' => 'boolean',
        ]);

        // Validate that we have either supplier or vendor information
        if (! $validated['supplier_id'] && ! $validated['vendor_name']) {
            return back()->withErrors(['vendor_name' => 'Debe seleccionar un proveedor o ingresar información del proveedor']);
        }

        $validated['conjunto_config_id'] = $conjunto->id;
        $validated['created_by'] = auth()->id();
        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;

        // Set initial status
        $category = ExpenseCategory::find($validated['expense_category_id']);
        if ($request->boolean('submit_for_approval') && $category->requires_approval) {
            $validated['status'] = 'pendiente';
        } else {
            $validated['status'] = 'borrador';
        }

        $expense = $this->expenseService->create($validated);

        return redirect()->route('expenses.show', $expense)
            ->with('success', 'Gasto creado exitosamente');
    }

    public function edit(Expense $expense)
    {
        if (! in_array($expense->status, ['borrador', 'pendiente', 'rechazado'])) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'Este gasto no puede ser editado en su estado actual');
        }

        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        $categories = ExpenseCategory::forConjunto($conjunto->id)
            ->active()
            ->with(['defaultDebitAccount', 'defaultCreditAccount'])
            ->orderBy('name')
            ->get();

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

        $suppliers = Supplier::forConjunto($conjunto->id)
            ->active()
            ->orderBy('name')
            ->get();

        return Inertia::render('Expenses/Edit', [
            'expense' => $expense,
            'categories' => $categories,
            'expenseAccounts' => $expenseAccounts,
            'assetAccounts' => $assetAccounts,
            'liabilityAccounts' => $liabilityAccounts,
            'suppliers' => $suppliers,
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        if (! in_array($expense->status, ['borrador', 'pendiente', 'rechazado'])) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'Este gasto no puede ser editado en su estado actual');
        }

        $validated = $request->validate([
            'expense_category_id' => 'required|exists:expense_categories,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'vendor_name' => 'nullable|string|max:255',
            'vendor_document' => 'nullable|string|max:50',
            'vendor_email' => 'nullable|email|max:255',
            'vendor_phone' => 'nullable|string|max:20',
            'description' => 'required|string|max:500',
            'expense_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:expense_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'debit_account_id' => 'required|exists:chart_of_accounts,id',
            'credit_account_id' => 'required|exists:chart_of_accounts,id',
            'notes' => 'nullable|string|max:1000',
            'submit_for_approval' => 'boolean',
        ]);

        // Validate that we have either supplier or vendor information
        if (! $validated['supplier_id'] && ! $validated['vendor_name']) {
            return back()->withErrors(['vendor_name' => 'Debe seleccionar un proveedor o ingresar información del proveedor']);
        }

        $validated['tax_amount'] = $validated['tax_amount'] ?? 0;

        // Handle status change if needed
        $category = ExpenseCategory::find($validated['expense_category_id']);
        if ($request->boolean('submit_for_approval') && $category->requires_approval && $expense->status === 'borrador') {
            $validated['status'] = 'pendiente';
        }

        $expense = $this->expenseService->update($expense, $validated);

        return redirect()->route('expenses.show', $expense)
            ->with('success', 'Gasto actualizado exitosamente');
    }

    public function destroy(Expense $expense)
    {
        if (! in_array($expense->status, ['borrador', 'rechazado'])) {
            return redirect()->route('expenses.index')
                ->with('error', 'Este gasto no puede ser eliminado en su estado actual');
        }

        $expense->delete();

        return redirect()->route('expenses.index')
            ->with('success', 'Gasto eliminado exitosamente');
    }

    public function approve(Expense $expense)
    {
        if (! $expense->can_be_approved) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'Este gasto no puede ser aprobado');
        }

        $expense->approve(auth()->id());

        $message = $expense->requiresCouncilApproval()
            ? 'Gasto enviado para aprobación del concejo exitosamente'
            : 'Gasto aprobado exitosamente';

        return redirect()->route('expenses.show', $expense)
            ->with('success', $message);
    }

    public function approveByCouncil(Expense $expense)
    {
        if ($expense->status !== 'pendiente_concejo') {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'Este gasto no está pendiente de aprobación del concejo');
        }

        $expense->approveByCouncil(auth()->id());

        return redirect()->route('expenses.show', $expense)
            ->with('success', 'Gasto aprobado por el concejo exitosamente');
    }

    public function reject(Request $request, Expense $expense)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (! in_array($expense->status, ['pendiente'])) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'Este gasto no puede ser rechazado');
        }

        $expense->reject($request->reason, auth()->id());

        return redirect()->route('expenses.show', $expense)
            ->with('success', 'Gasto rechazado exitosamente');
    }

    public function markAsPaid(Request $request, Expense $expense)
    {
        $request->validate([
            'payment_method' => 'nullable|string|max:100',
            'payment_reference' => 'nullable|string|max:255',
        ]);

        if (! $expense->can_be_paid) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'Este gasto no puede ser marcado como pagado');
        }

        $expense->markAsPaid(
            $request->payment_method,
            $request->payment_reference
        );

        return redirect()->route('expenses.show', $expense)
            ->with('success', 'Gasto marcado como pagado exitosamente');
    }

    public function cancel(Request $request, Expense $expense)
    {
        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        if (! $expense->can_be_cancelled) {
            return redirect()->route('expenses.show', $expense)
                ->with('error', 'Este gasto no puede ser cancelado');
        }

        $expense->cancel($request->reason);

        return redirect()->route('expenses.show', $expense)
            ->with('success', 'Gasto cancelado exitosamente');
    }

    public function duplicate(Expense $expense)
    {
        $newExpense = $this->expenseService->duplicate($expense);

        return redirect()->route('expenses.edit', $newExpense)
            ->with('success', 'Gasto duplicado exitosamente');
    }
}
