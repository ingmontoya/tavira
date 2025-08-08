<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\User;
use App\Notifications\ExpenseApprovalNotification;
use App\Settings\ExpenseSettings;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class ExpenseNotificationService
{
    public function notifyPendingApproval(Expense $expense): void
    {
        $settings = app(ExpenseSettings::class);

        if (! $settings->notify_on_pending_approval) {
            return;
        }

        try {
            // Get users who can approve expenses
            $approvers = User::role(['admin_conjunto', 'superadmin', 'consejo'])
                ->where('conjunto_config_id', $expense->conjunto_config_id)
                ->get();

            if ($approvers->isEmpty()) {
                Log::warning("No approvers found for expense {$expense->expense_number}");

                return;
            }

            Notification::send(
                $approvers,
                new ExpenseApprovalNotification($expense, 'pending_approval')
            );

            Log::info("Pending approval notification sent for expense {$expense->expense_number} to {$approvers->count()} users");
        } catch (\Exception $e) {
            Log::error("Failed to send pending approval notification for expense {$expense->expense_number}: ".$e->getMessage());
        }
    }

    public function notifyCouncilApproval(Expense $expense): void
    {
        $settings = app(ExpenseSettings::class);

        if (! $settings->notify_on_pending_approval || ! $settings->council_approval_notification_email) {
            return;
        }

        try {
            // Get council members
            $councilMembers = User::role(['consejo', 'superadmin'])
                ->where('conjunto_config_id', $expense->conjunto_config_id)
                ->get();

            // Also send to specific council email if configured
            if ($settings->council_approval_notification_email) {
                $councilMembers = $councilMembers->merge([
                    (object) [
                        'name' => 'Concejo de Administración',
                        'email' => $settings->council_approval_notification_email,
                    ],
                ]);
            }

            if ($councilMembers->isEmpty()) {
                Log::warning("No council members found for expense {$expense->expense_number}");

                return;
            }

            Notification::send(
                $councilMembers,
                new ExpenseApprovalNotification($expense, 'pending_council')
            );

            Log::info("Council approval notification sent for expense {$expense->expense_number} to {$councilMembers->count()} recipients");
        } catch (\Exception $e) {
            Log::error("Failed to send council approval notification for expense {$expense->expense_number}: ".$e->getMessage());
        }
    }

    public function notifyApproval(Expense $expense): void
    {
        $settings = app(ExpenseSettings::class);

        if (! $settings->notify_on_approval_granted) {
            return;
        }

        try {
            $expense->createdBy->notify(
                new ExpenseApprovalNotification($expense, 'approved')
            );

            Log::info("Approval notification sent for expense {$expense->expense_number} to {$expense->createdBy->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send approval notification for expense {$expense->expense_number}: ".$e->getMessage());
        }
    }

    public function notifyRejection(Expense $expense): void
    {
        $settings = app(ExpenseSettings::class);

        if (! $settings->notify_on_approval_rejected) {
            return;
        }

        try {
            $expense->createdBy->notify(
                new ExpenseApprovalNotification($expense, 'rejected')
            );

            Log::info("Rejection notification sent for expense {$expense->expense_number} to {$expense->createdBy->email}");
        } catch (\Exception $e) {
            Log::error("Failed to send rejection notification for expense {$expense->expense_number}: ".$e->getMessage());
        }
    }

    public function notifyOverdue(Expense $expense): void
    {
        try {
            // Notify financial managers about overdue expenses
            $financialManagers = User::role(['admin_conjunto', 'superadmin'])
                ->where('conjunto_config_id', $expense->conjunto_config_id)
                ->get();

            if ($financialManagers->isEmpty()) {
                Log::warning("No financial managers found for overdue expense {$expense->expense_number}");

                return;
            }

            Notification::send(
                $financialManagers,
                new ExpenseApprovalNotification($expense, 'overdue')
            );

            Log::info("Overdue notification sent for expense {$expense->expense_number} to {$financialManagers->count()} managers");
        } catch (\Exception $e) {
            Log::error("Failed to send overdue notification for expense {$expense->expense_number}: ".$e->getMessage());
        }
    }

    public function sendDailySummary(): void
    {
        try {
            $conjuntos = \App\Models\ConjuntoConfig::where('is_active', true)->get();

            foreach ($conjuntos as $conjunto) {
                $this->sendDailySummaryForConjunto($conjunto);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send daily summary: '.$e->getMessage());
        }
    }

    protected function sendDailySummaryForConjunto(\App\Models\ConjuntoConfig $conjunto): void
    {
        $pendingCount = Expense::forConjunto($conjunto->id)->pending()->count();
        $councilPendingCount = Expense::forConjunto($conjunto->id)->where('status', 'pendiente_concejo')->count();
        $overdueCount = Expense::forConjunto($conjunto->id)->overdue()->count();

        // Only send summary if there's something to report
        if ($pendingCount + $councilPendingCount + $overdueCount === 0) {
            return;
        }

        $managers = User::role(['admin_conjunto', 'superadmin'])
            ->where('conjunto_config_id', $conjunto->id)
            ->get();

        if ($managers->isEmpty()) {
            return;
        }

        // TODO: Create DailySummaryNotification class
        Log::info("Daily summary for conjunto {$conjunto->name}: {$pendingCount} pending, {$councilPendingCount} council pending, {$overdueCount} overdue");
    }
}
