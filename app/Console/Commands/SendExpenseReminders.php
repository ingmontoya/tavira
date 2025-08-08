<?php

namespace App\Console\Commands;

use App\Models\ConjuntoConfig;
use App\Models\Expense;
use App\Services\ExpenseNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SendExpenseReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'expenses:send-reminders 
                            {--type=overdue : Type of reminders to send (overdue, pending, daily-summary)}
                            {--conjunto= : Specific conjunto ID to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send expense reminders and notifications';

    protected ExpenseNotificationService $notificationService;

    public function __construct(ExpenseNotificationService $notificationService)
    {
        parent::__construct();
        $this->notificationService = $notificationService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $type = $this->option('type');
        $conjuntoId = $this->option('conjunto');

        try {
            switch ($type) {
                case 'overdue':
                    $this->sendOverdueReminders($conjuntoId);
                    break;

                case 'pending':
                    $this->sendPendingReminders($conjuntoId);
                    break;

                case 'daily-summary':
                    $this->sendDailySummary($conjuntoId);
                    break;

                default:
                    $this->error("Unknown reminder type: {$type}");

                    return 1;
            }

            return 0;
        } catch (\Exception $e) {
            $this->error('Error sending reminders: '.$e->getMessage());
            Log::error('Error in SendExpenseReminders command: '.$e->getMessage(), [
                'type' => $type,
                'conjunto_id' => $conjuntoId,
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }

    protected function sendOverdueReminders(?int $conjuntoId = null): void
    {
        $this->info('Sending overdue expense reminders...');

        $query = Expense::with(['createdBy', 'expenseCategory'])
            ->overdue()
            ->where('status', '!=', 'cancelado');

        if ($conjuntoId) {
            $query->forConjunto($conjuntoId);
        }

        $overdueExpenses = $query->get();

        if ($overdueExpenses->isEmpty()) {
            $this->info('No overdue expenses found.');

            return;
        }

        $count = 0;
        foreach ($overdueExpenses as $expense) {
            try {
                $this->notificationService->notifyOverdue($expense);
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to send overdue notification for expense {$expense->expense_number}: ".$e->getMessage());
            }
        }

        $this->info("Sent overdue reminders for {$count} expenses.");
        Log::info("Sent overdue reminders for {$count} expenses");
    }

    protected function sendPendingReminders(?int $conjuntoId = null): void
    {
        $this->info('Sending pending approval reminders...');

        $query = Expense::with(['createdBy', 'expenseCategory'])
            ->where('status', 'pendiente')
            ->where('created_at', '<', now()->subDays(2)); // Only for expenses older than 2 days

        if ($conjuntoId) {
            $query->forConjunto($conjuntoId);
        }

        $pendingExpenses = $query->get();

        if ($pendingExpenses->isEmpty()) {
            $this->info('No pending expenses requiring reminders.');

            return;
        }

        $count = 0;
        foreach ($pendingExpenses as $expense) {
            try {
                $this->notificationService->notifyPendingApproval($expense);
                $count++;
            } catch (\Exception $e) {
                $this->error("Failed to send pending reminder for expense {$expense->expense_number}: ".$e->getMessage());
            }
        }

        $this->info("Sent pending reminders for {$count} expenses.");
        Log::info("Sent pending reminders for {$count} expenses");
    }

    protected function sendDailySummary(?int $conjuntoId = null): void
    {
        $this->info('Sending daily expense summary...');

        if ($conjuntoId) {
            $conjunto = ConjuntoConfig::find($conjuntoId);
            if (! $conjunto) {
                $this->error("Conjunto with ID {$conjuntoId} not found.");

                return;
            }
            $this->sendSummaryForConjunto($conjunto);
        } else {
            $conjuntos = ConjuntoConfig::where('is_active', true)->get();
            foreach ($conjuntos as $conjunto) {
                $this->sendSummaryForConjunto($conjunto);
            }
        }

        $this->info('Daily summary sent successfully.');
    }

    protected function sendSummaryForConjunto(ConjuntoConfig $conjunto): void
    {
        $pendingCount = Expense::forConjunto($conjunto->id)->pending()->count();
        $councilPendingCount = Expense::forConjunto($conjunto->id)->where('status', 'pendiente_concejo')->count();
        $overdueCount = Expense::forConjunto($conjunto->id)->overdue()->count();

        $this->info("Conjunto {$conjunto->name}: {$pendingCount} pending, {$councilPendingCount} council pending, {$overdueCount} overdue");

        // Only send summary if there's something to report
        if ($pendingCount + $councilPendingCount + $overdueCount > 0) {
            $this->notificationService->sendDailySummary();
        }
    }
}
