<?php

namespace App\Console\Commands;

use App\Models\Apartment;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class UpdateApartmentPaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apartments:update-payment-status
                          {--apartment= : Update specific apartment by ID}
                          {--dry-run : Show what would be updated without saving}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update payment status for all apartments based on their unpaid invoices (tenant context required)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check if we're in a tenant context
        if (! tenancy()->initialized) {
            $this->error('This command must be run in a tenant context.');
            $this->line('Use: php artisan tenants:run apartments:update-payment-status');
            return self::FAILURE;
        }

        $this->info('=== Updating Apartment Payment Status ===');
        $this->info('Tenant: ' . tenant('id'));
        $this->newLine();

        $query = Apartment::query();

        // Filter by specific apartment if provided
        if ($apartmentId = $this->option('apartment')) {
            $query->where('id', $apartmentId);
        }

        $apartments = $query->get();
        $totalApartments = $apartments->count();

        if ($totalApartments === 0) {
            $this->warn('No apartments found.');

            return self::SUCCESS;
        }

        $this->info("Processing {$totalApartments} apartment(s)...");
        $this->newLine();

        $progressBar = $this->output->createProgressBar($totalApartments);
        $progressBar->start();

        $updated = 0;
        $statusCounts = [
            'current' => 0,
            'overdue_30' => 0,
            'overdue_60' => 0,
            'overdue_90' => 0,
            'overdue_90_plus' => 0,
        ];
        $changes = [];

        foreach ($apartments as $apartment) {
            $oldStatus = $apartment->payment_status;
            $newStatus = $this->calculatePaymentStatus($apartment);

            // Update counts
            $statusCounts[$newStatus] = ($statusCounts[$newStatus] ?? 0) + 1;

            // Track changes
            if ($oldStatus !== $newStatus) {
                $changes[] = [
                    'number' => $apartment->number,
                    'old' => $oldStatus ?: 'null',
                    'new' => $newStatus,
                ];
                $updated++;
            }

            // Save if not dry-run
            if (! $this->option('dry-run')) {
                $apartment->payment_status = $newStatus;
                $apartment->outstanding_balance = $this->calculateOutstandingBalance($apartment);
                $apartment->save();
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        // Show changes
        if (count($changes) > 0) {
            $this->info('Status Changes:');
            $this->table(
                ['Apartment', 'Old Status', 'New Status'],
                array_map(fn ($change) => [
                    $change['number'],
                    $change['old'],
                    $change['new'],
                ], $changes)
            );
            $this->newLine();
        } else {
            $this->info('No status changes detected.');
            $this->newLine();
        }

        // Show summary
        $this->info('=== Summary ===');
        $this->line("Total apartments processed: {$totalApartments}");
        $this->line("Apartments updated: {$updated}");
        $this->newLine();

        $this->table(
            ['Status', 'Count'],
            [
                ['Al día', $statusCounts['current']],
                ['0-30 días de mora', $statusCounts['overdue_30']],
                ['60 días de mora', $statusCounts['overdue_60']],
                ['90 días de mora', $statusCounts['overdue_90']],
                ['+90 días de mora', $statusCounts['overdue_90_plus']],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN: No changes were saved to the database.');
        } else {
            $this->success('Payment statuses updated successfully!');
        }

        return self::SUCCESS;
    }

    /**
     * Calculate payment status based on oldest unpaid invoice
     */
    private function calculatePaymentStatus(Apartment $apartment): string
    {
        // Get the oldest unpaid invoice
        $oldestUnpaidInvoice = $apartment->invoices()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date', 'asc')
            ->first();

        if (! $oldestUnpaidInvoice) {
            // No unpaid invoices, apartment is current
            return 'current';
        }

        // Calculate days overdue based on the oldest unpaid invoice
        $today = now()->startOfDay();
        $dueDate = $oldestUnpaidInvoice->due_date->startOfDay();

        if ($today->lte($dueDate)) {
            // Not overdue yet, apartment is current
            return 'current';
        }

        // Calculate days overdue
        $daysOverdue = $dueDate->diffInDays($today);

        if ($daysOverdue >= 90) {
            return 'overdue_90_plus';
        } elseif ($daysOverdue >= 60) {
            return 'overdue_90';
        } elseif ($daysOverdue >= 30) {
            return 'overdue_60';
        } else {
            return 'overdue_30';
        }
    }

    /**
     * Calculate total outstanding balance
     */
    private function calculateOutstandingBalance(Apartment $apartment): float
    {
        return $apartment->invoices()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum('balance_amount');
    }

    /**
     * Success message helper
     */
    private function success(string $message): void
    {
        $this->line("<fg=green>✓</> {$message}");
    }
}
