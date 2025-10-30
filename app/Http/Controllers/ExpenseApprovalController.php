<?php

namespace App\Http\Controllers;

use App\Models\ConjuntoConfig;
use App\Models\Expense;
use App\Services\ExpenseService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ExpenseApprovalController extends Controller
{
    protected ExpenseService $expenseService;

    public function __construct(ExpenseService $expenseService)
    {
        $this->expenseService = $expenseService;
    }

    public function dashboard()
    {
        $conjunto = ConjuntoConfig::where('is_active', true)->first();

        // Get pending approvals
        $pendingApprovals = Expense::with(['expenseCategory', 'provider', 'debitAccount', 'creditAccount', 'createdBy'])
            ->forConjunto($conjunto->id)
            ->where('status', 'pendiente')
            ->orderBy('expense_date', 'asc')
            ->get();

        // Get council pending approvals
        $councilPendingApprovals = Expense::with(['expenseCategory', 'provider', 'debitAccount', 'creditAccount', 'createdBy', 'approvedBy'])
            ->forConjunto($conjunto->id)
            ->where('status', 'pendiente_concejo')
            ->orderBy('approved_at', 'asc')
            ->get();

        // Get overdue expenses (approved but not paid)
        $overdueExpenses = Expense::with(['expenseCategory', 'provider'])
            ->forConjunto($conjunto->id)
            ->overdue()
            ->orderBy('due_date', 'asc')
            ->get();

        // Get recent approvals (last 7 days)
        $recentApprovals = Expense::with(['expenseCategory', 'provider', 'approvedBy'])
            ->forConjunto($conjunto->id)
            ->where('status', 'aprobado')
            ->where('approved_at', '>=', now()->subDays(7))
            ->orderBy('approved_at', 'desc')
            ->limit(10)
            ->get();

        // Summary stats
        $stats = [
            'pending_regular_count' => $pendingApprovals->count(),
            'pending_council_count' => $councilPendingApprovals->count(),
            'overdue_count' => $overdueExpenses->count(),
            'pending_total_amount' => $pendingApprovals->sum('total_amount'),
            'council_pending_total_amount' => $councilPendingApprovals->sum('total_amount'),
            'overdue_total_amount' => $overdueExpenses->sum('total_amount'),
        ];

        return Inertia::render('Expenses/ApprovalDashboard', [
            'pendingApprovals' => $pendingApprovals,
            'councilPendingApprovals' => $councilPendingApprovals,
            'overdueExpenses' => $overdueExpenses,
            'recentApprovals' => $recentApprovals,
            'stats' => $stats,
        ]);
    }

    public function bulkApprove(Request $request)
    {
        $validated = $request->validate([
            'expense_ids' => 'required|array',
            'expense_ids.*' => 'exists:expenses,id',
        ]);

        $results = $this->expenseService->bulkApprove(
            $validated['expense_ids'],
            auth()->id()
        );

        $message = "Aprobados: {$results['approved']} gastos";
        if (! empty($results['errors'])) {
            $message .= '. Errores: '.implode(', ', $results['errors']);
        }

        return redirect()->back()->with('success', $message);
    }

    public function bulkReject(Request $request)
    {
        $validated = $request->validate([
            'expense_ids' => 'required|array',
            'expense_ids.*' => 'exists:expenses,id',
            'reason' => 'required|string|max:500',
        ]);

        $rejected = 0;
        $errors = [];

        foreach ($validated['expense_ids'] as $expenseId) {
            $expense = Expense::find($expenseId);
            if ($expense && $expense->status === 'pendiente') {
                try {
                    $expense->reject($validated['reason'], auth()->id());
                    $rejected++;
                } catch (\Exception $e) {
                    $errors[] = "Error en gasto {$expense->expense_number}: ".$e->getMessage();
                }
            }
        }

        $message = "Rechazados: {$rejected} gastos";
        if (! empty($errors)) {
            $message .= '. Errores: '.implode(', ', $errors);
        }

        return redirect()->back()->with('success', $message);
    }
}
