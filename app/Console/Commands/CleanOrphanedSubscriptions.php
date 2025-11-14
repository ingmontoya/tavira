<?php

namespace App\Console\Commands;

use App\Models\TenantSubscription;
use Illuminate\Console\Command;

class CleanOrphanedSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:clean-orphaned {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean orphaned subscriptions (without user_id) that may cause redirect issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking for orphaned subscriptions...');
        $this->newLine();

        // Find subscriptions without user_id
        $orphanedSubscriptions = TenantSubscription::whereNull('user_id')->get();

        if ($orphanedSubscriptions->isEmpty()) {
            $this->info('✅ No orphaned subscriptions found. Everything looks good!');

            return 0;
        }

        $this->warn("Found {$orphanedSubscriptions->count()} orphaned subscription(s):");
        $this->newLine();

        // Display orphaned subscriptions in a table
        $tableData = $orphanedSubscriptions->map(function ($sub) {
            return [
                'ID' => $sub->id,
                'Plan' => $sub->plan_name,
                'Status' => $sub->status,
                'Amount' => '$'.number_format($sub->amount, 0),
                'Tenant ID' => $sub->tenant_id ?? 'NULL',
                'User ID' => 'NULL',
                'Paid At' => $sub->paid_at?->format('Y-m-d H:i') ?? 'NULL',
                'Expires At' => $sub->expires_at?->format('Y-m-d H:i') ?? 'Never',
            ];
        })->toArray();

        $this->table(
            ['ID', 'Plan', 'Status', 'Amount', 'Tenant ID', 'User ID', 'Paid At', 'Expires At'],
            $tableData
        );

        $this->newLine();

        if ($this->option('dry-run')) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
            $this->warn('These subscriptions would be marked as "orphaned" status');

            return 0;
        }

        if (! $this->confirm('Do you want to mark these subscriptions as "orphaned" status?', true)) {
            $this->info('Operation cancelled.');

            return 0;
        }

        // Mark orphaned subscriptions with a special status
        $updated = 0;
        foreach ($orphanedSubscriptions as $subscription) {
            $subscription->update(['status' => 'orphaned']);
            $updated++;
        }

        $this->newLine();
        $this->info("✅ Successfully marked {$updated} subscription(s) as orphaned");
        $this->info('These subscriptions will no longer interfere with the redirect logic.');

        return 0;
    }
}
