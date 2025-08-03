<?php

namespace App\Console\Commands;

use App\Models\BudgetExecution;
use App\Models\ConjuntoConfig;
use App\Models\User;
use App\Notifications\BudgetOverspendAlert;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class CheckBudgetOverspend extends Command
{
    protected $signature = 'budget:check-overspend {--month=} {--year=} {--threshold=10}';

    protected $description = 'Check for budget overspend and send alerts';

    public function handle()
    {
        $month = $this->option('month') ?: now()->month;
        $year = $this->option('year') ?: now()->year;
        $threshold = (float) $this->option('threshold');

        $conjuntoConfig = ConjuntoConfig::where('is_active', true)->first();
        
        if (!$conjuntoConfig) {
            $this->error('No active conjunto configuration found.');
            return 1;
        }

        $this->info("Checking budget overspend for {$month}/{$year} with threshold {$threshold}%");

        $overspendExecutions = BudgetExecution::whereHas('budgetItem.budget', function ($query) use ($conjuntoConfig) {
            $query->where('conjunto_config_id', $conjuntoConfig->id)
                  ->where('status', 'active');
        })
        ->byPeriod($month, $year)
        ->get()
        ->filter(function ($execution) use ($threshold) {
            return $execution->isOverBudgetByThreshold($threshold);
        });

        if ($overspendExecutions->isEmpty()) {
            $this->info('No budget overspend found.');
            return 0;
        }

        $this->info("Found {$overspendExecutions->count()} budget executions over threshold.");

        // Group by alert type
        $dangerAlerts = $overspendExecutions->filter(function ($execution) {
            return $execution->isOverBudgetByThreshold(10);
        });

        $warningAlerts = $overspendExecutions->filter(function ($execution) {
            return $execution->isOverBudgetByThreshold(5) && !$execution->isOverBudgetByThreshold(10);
        });

        // Get users to notify (administrators and finance users)
        $usersToNotify = User::whereHas('roles', function ($query) {
            $query->whereIn('name', ['admin', 'finance', 'manager']);
        })->get();

        if ($usersToNotify->isEmpty()) {
            $this->warn('No users found to notify.');
            return 0;
        }

        // Send notifications
        if ($dangerAlerts->isNotEmpty()) {
            $this->sendAlerts($usersToNotify, $dangerAlerts, 'danger', $month, $year);
        }

        if ($warningAlerts->isNotEmpty()) {
            $this->sendAlerts($usersToNotify, $warningAlerts, 'warning', $month, $year);
        }

        $this->info('Budget overspend alerts sent successfully.');
        return 0;
    }

    private function sendAlerts($users, $executions, $type, $month, $year)
    {
        $alertData = [
            'period' => ['month' => $month, 'year' => $year],
            'type' => $type,
            'executions' => $executions->map(function ($execution) {
                return [
                    'account_name' => $execution->budgetItem->account->name,
                    'account_code' => $execution->budgetItem->account->code,
                    'budgeted_amount' => $execution->budgeted_amount,
                    'actual_amount' => $execution->actual_amount,
                    'variance_amount' => $execution->variance_amount,
                    'variance_percentage' => $execution->variance_percentage,
                ];
            })->toArray(),
        ];

        Notification::send($users, new BudgetOverspendAlert($alertData));
        
        $this->info("Sent {$type} alerts to {$users->count()} users for {$executions->count()} executions.");
    }
}
