<?php

namespace App\Console\Commands;

use App\Models\TenantSubscription;
use App\Models\User;
use Illuminate\Console\Command;

class UpdateSubscriptionUserTenant extends Command
{
    protected $signature = 'subscriptions:update-user-tenant {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Update null user_id and tenant_id in tenant_subscriptions using payment_data';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        $this->info('Checking tenant_subscriptions for null user_id or tenant_id...');

        $subscriptions = TenantSubscription::where(function ($query) {
            $query->whereNull('user_id')->orWhereNull('tenant_id');
        })->get();

        $this->info("Found {$subscriptions->count()} subscriptions with null user_id or tenant_id");

        $updated = 0;
        $errors = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $paymentData = $subscription->payment_data;
                $changes = [];

                // Try to get user_id from payment_data
                if (! $subscription->user_id && isset($paymentData['user_id'])) {
                    $changes['user_id'] = $paymentData['user_id'];
                }

                // If still no user_id, try to find by email
                if (! isset($changes['user_id']) && ! $subscription->user_id && isset($paymentData['customer_email'])) {
                    $user = User::where('email', $paymentData['customer_email'])->first();
                    if ($user) {
                        $changes['user_id'] = $user->id;

                        // Also get tenant_id from user if not set
                        if (! $subscription->tenant_id && $user->tenant_id) {
                            $changes['tenant_id'] = $user->tenant_id;
                        }
                    }
                }

                // Try to get tenant_id from payment_data if still null
                if (! isset($changes['tenant_id']) && ! $subscription->tenant_id && isset($paymentData['tenant_id'])) {
                    $changes['tenant_id'] = $paymentData['tenant_id'];
                }

                if (! empty($changes)) {
                    $this->line("Subscription {$subscription->id}:");
                    foreach ($changes as $field => $value) {
                        $this->line("  - {$field}: null -> {$value}");
                    }

                    if (! $isDryRun) {
                        $subscription->update($changes);
                        $updated++;
                    }
                } else {
                    $this->warn("No data found to update subscription {$subscription->id}");
                    $this->line('  Payment data keys: '.implode(', ', array_keys($paymentData)));
                }

            } catch (\Exception $e) {
                $this->error("Error processing subscription {$subscription->id}: {$e->getMessage()}");
                $errors++;
            }
        }

        if ($isDryRun) {
            $this->info("DRY RUN: Would update {$updated} subscriptions");
            $this->info('Run without --dry-run to actually update the records');
        } else {
            $this->info("Updated {$updated} subscriptions successfully");
        }

        if ($errors > 0) {
            $this->warn("Encountered {$errors} errors during processing");
        }

        return 0;
    }
}
