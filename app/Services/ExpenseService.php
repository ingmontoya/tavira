<?php

namespace App\Services;

use App\Models\Expense;
use Illuminate\Support\Facades\DB;

class ExpenseService
{
    public function create(array $data): Expense
    {
        return DB::transaction(function () use ($data) {
            $expense = Expense::create($data);

            // Always create accounting transaction when creating an expense
            // The transaction status will be managed separately from the expense status
            $expense->createAccountingTransaction();

            return $expense->load(['expenseCategory', 'provider', 'debitAccount', 'creditAccount']);
        });
    }

    public function update(Expense $expense, array $data): Expense
    {
        return DB::transaction(function () use ($expense, $data) {
            $expense->update($data);

            // If status changed to approved and no accounting transaction exists
            if ($expense->status === 'aprobado' && ! $expense->accountingTransactions()->exists()) {
                $expense->createAccountingTransaction();
            }

            return $expense->load(['expenseCategory', 'provider', 'debitAccount', 'creditAccount']);
        });
    }

    public function duplicate(Expense $expense): Expense
    {
        return DB::transaction(function () use ($expense) {
            $newExpense = $expense->replicate();
            $newExpense->status = 'borrador';
            $newExpense->expense_number = null; // Will be auto-generated
            $newExpense->approved_by = null;
            $newExpense->approved_at = null;
            $newExpense->paid_at = null;
            $newExpense->payment_method = null;
            $newExpense->payment_reference = null;
            $newExpense->created_by = auth()->id();
            $newExpense->save();

            return $newExpense;
        });
    }

    public function bulkApprove(array $expenseIds, int $approvedBy): array
    {
        $results = ['approved' => 0, 'errors' => []];

        DB::transaction(function () use ($expenseIds, $approvedBy, &$results) {
            $expenses = Expense::whereIn('id', $expenseIds)
                ->where('status', 'pendiente')
                ->get();

            foreach ($expenses as $expense) {
                try {
                    if ($expense->can_be_approved) {
                        $expense->approve($approvedBy);
                        $results['approved']++;
                    } else {
                        $results['errors'][] = "Gasto {$expense->expense_number} no puede ser aprobado";
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Error en gasto {$expense->expense_number}: ".$e->getMessage();
                }
            }
        });

        return $results;
    }

    public function getExpenseSummaryByCategory(int $conjuntoConfigId, string $startDate, string $endDate): array
    {
        return Expense::forConjunto($conjuntoConfigId)
            ->whereBetween('expense_date', [$startDate, $endDate])
            ->whereIn('status', ['aprobado', 'pagado'])
            ->with('expenseCategory')
            ->get()
            ->groupBy('expenseCategory.name')
            ->map(function ($expenses, $categoryName) {
                return [
                    'category' => $categoryName,
                    'total_amount' => $expenses->sum('total_amount'),
                    'count' => $expenses->count(),
                    'avg_amount' => $expenses->avg('total_amount'),
                ];
            })
            ->values()
            ->toArray();
    }

    public function getMonthlyExpenseTrend(int $conjuntoConfigId, int $year): array
    {
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $monthlyData[] = [
                'month' => $month,
                'month_name' => date('F', mktime(0, 0, 0, $month, 1)),
                'total_amount' => Expense::forConjunto($conjuntoConfigId)
                    ->whereYear('expense_date', $year)
                    ->whereMonth('expense_date', $month)
                    ->whereIn('status', ['aprobado', 'pagado'])
                    ->sum('total_amount'),
                'count' => Expense::forConjunto($conjuntoConfigId)
                    ->whereYear('expense_date', $year)
                    ->whereMonth('expense_date', $month)
                    ->whereIn('status', ['aprobado', 'pagado'])
                    ->count(),
            ];
        }

        return $monthlyData;
    }

    public function getOverdueExpenses(int $conjuntoConfigId): array
    {
        $expenses = Expense::forConjunto($conjuntoConfigId)
            ->overdue()
            ->with(['expenseCategory', 'provider', 'createdBy'])
            ->orderBy('due_date')
            ->get();

        return $expenses->map(function ($expense) {
            return [
                'id' => $expense->id,
                'expense_number' => $expense->expense_number,
                'vendor_name' => $expense->getVendorDisplayName(),
                'description' => $expense->description,
                'total_amount' => $expense->total_amount,
                'due_date' => $expense->due_date,
                'days_overdue' => $expense->days_overdue,
                'category' => $expense->expenseCategory->name,
                'status' => $expense->status,
            ];
        })->toArray();
    }

    public function getPendingApprovals(int $conjuntoConfigId): array
    {
        $expenses = Expense::forConjunto($conjuntoConfigId)
            ->pending()
            ->with(['expenseCategory', 'provider', 'createdBy'])
            ->orderBy('created_at')
            ->get();

        return $expenses->map(function ($expense) {
            return [
                'id' => $expense->id,
                'expense_number' => $expense->expense_number,
                'vendor_name' => $expense->getVendorDisplayName(),
                'description' => $expense->description,
                'total_amount' => $expense->total_amount,
                'expense_date' => $expense->expense_date,
                'category' => $expense->expenseCategory->name,
                'created_by' => $expense->createdBy->name,
                'created_at' => $expense->created_at,
            ];
        })->toArray();
    }
}
