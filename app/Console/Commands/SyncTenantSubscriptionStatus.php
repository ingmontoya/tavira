<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantSubscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SyncTenantSubscriptionStatus extends Command
{
    protected $signature = 'tenants:sync-subscription-status {--dry-run : Show what would be updated without making changes}';

    protected $description = 'Sync tenant subscription status from central tenant_subscriptions table';

    public function handle()
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('🔍 DRY RUN - No changes will be made');
            $this->line('');
        }

        $this->info('🔄 Syncing tenant subscription statuses...');

        $tenants = Tenant::all();
        $updated = 0;
        $suspended = 0;
        $activated = 0;
        $errors = 0;

        foreach ($tenants as $tenant) {
            try {
                // Get the most recent active subscription for this tenant
                $activeSubscription = TenantSubscription::where(function ($query) use ($tenant) {
                    $query->where('tenant_id', $tenant->id)
                        ->orWhere('user_id', $tenant->admin_user_id);
                })
                    ->where('status', 'active')
                    ->orderBy('created_at', 'desc')
                    ->first();

                $oldStatus = $tenant->subscription_status;
                $newStatus = 'pending';
                $expiresAt = null;

                if ($activeSubscription) {
                    // Check if subscription is expired
                    if ($activeSubscription->expires_at && Carbon::parse($activeSubscription->expires_at)->isPast()) {
                        $newStatus = 'expired';
                        $expiresAt = $activeSubscription->expires_at;
                    } else {
                        $newStatus = 'active';
                        $expiresAt = $activeSubscription->expires_at;
                    }
                } else {
                    // Check if there's any subscription (even inactive) to determine if it should be suspended vs pending
                    $anySubscription = TenantSubscription::where(function ($query) use ($tenant) {
                        $query->where('tenant_id', $tenant->id)
                            ->orWhere('user_id', $tenant->admin_user_id);
                    })->first();

                    if ($anySubscription) {
                        $newStatus = 'suspended';
                    }
                }

                // Update tenant if status changed
                if ($oldStatus !== $newStatus || $tenant->subscription_expires_at != $expiresAt) {
                    $changes = [
                        'subscription_status' => $newStatus,
                        'subscription_expires_at' => $expiresAt,
                        'subscription_last_checked_at' => now(),
                    ];

                    if ($oldStatus !== $newStatus) {
                        $this->line("📋 Tenant {$tenant->id}:");
                        $this->line("   Status: {$oldStatus} → {$newStatus}");
                        if ($expiresAt) {
                            $this->line("   Expires: {$expiresAt}");
                        }

                        if ($newStatus === 'suspended' || $newStatus === 'expired') {
                            $suspended++;
                        } elseif ($newStatus === 'active') {
                            $activated++;
                        }
                    }

                    if (! $isDryRun) {
                        $tenant->update($changes);
                    }

                    $updated++;
                } else {
                    // Just update the last checked timestamp
                    if (! $isDryRun) {
                        $tenant->update(['subscription_last_checked_at' => now()]);
                    }
                }

            } catch (\Exception $e) {
                $this->error("❌ Error processing tenant {$tenant->id}: {$e->getMessage()}");
                $errors++;
            }
        }

        $this->line('');
        $this->info('✅ Sync completed!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total tenants processed', $tenants->count()],
                ['Status changes', $updated],
                ['Activated', $activated],
                ['Suspended/Expired', $suspended],
                ['Errors', $errors],
            ]
        );

        if ($isDryRun) {
            $this->warn('🔸 This was a dry run. Run without --dry-run to apply changes.');
        }

        return $errors > 0 ? 1 : 0;
    }
}
