<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncTenantDataColumns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenants:sync-columns {--dry-run : Show what would be updated without making changes} {--tenant= : Sync specific tenant by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize tenant data field with individual columns';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $specificTenant = $this->option('tenant');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        // Get tenants to process
        $query = Tenant::query();
        if ($specificTenant) {
            $query->where('id', $specificTenant);
        }

        $tenants = $query->get();
        $this->info("📋 Found {$tenants->count()} tenant(s) to process");

        $updated = 0;
        $skipped = 0;

        foreach ($tenants as $tenant) {
            $this->info("🔧 Processing tenant: {$tenant->id}");

            // Get raw data from JSON field
            $rawData = DB::table('tenants')->where('id', $tenant->id)->value('data');
            $data = $rawData ? json_decode($rawData, true) : [];

            if (empty($data)) {
                $this->warn('  ⚠️  No data found in JSON field, skipping');
                $skipped++;

                continue;
            }

            // Prepare updates for individual columns
            $updates = [];

            // Check and prepare subscription columns
            if (isset($data['subscription_status']) && $tenant->subscription_status !== $data['subscription_status']) {
                $updates['subscription_status'] = $data['subscription_status'];
                $this->line("    📝 subscription_status: '{$tenant->subscription_status}' → '{$data['subscription_status']}'");
            }

            if (isset($data['subscription_plan']) && $tenant->subscription_plan !== $data['subscription_plan']) {
                $updates['subscription_plan'] = $data['subscription_plan'];
                $this->line("    📝 subscription_plan: '{$tenant->subscription_plan}' → '{$data['subscription_plan']}'");
            }

            if (isset($data['subscription_expires_at']) && $tenant->subscription_expires_at?->toISOString() !== $data['subscription_expires_at']) {
                $updates['subscription_expires_at'] = $data['subscription_expires_at'];
                $this->line("    📝 subscription_expires_at: '{$tenant->subscription_expires_at}' → '{$data['subscription_expires_at']}'");
            }

            if (isset($data['subscription_renewed_at']) && $tenant->subscription_renewed_at?->toISOString() !== $data['subscription_renewed_at']) {
                $updates['subscription_renewed_at'] = $data['subscription_renewed_at'];
                $this->line("    📝 subscription_renewed_at: '{$tenant->subscription_renewed_at}' → '{$data['subscription_renewed_at']}'");
            }

            if (isset($data['subscription_last_checked_at']) && $tenant->subscription_last_checked_at?->toISOString() !== $data['subscription_last_checked_at']) {
                $updates['subscription_last_checked_at'] = $data['subscription_last_checked_at'];
                $this->line("    📝 subscription_last_checked_at: '{$tenant->subscription_last_checked_at}' → '{$data['subscription_last_checked_at']}'");
            }

            // Show other important data fields for reference
            if (isset($data['name'])) {
                $this->line("    ℹ️  name: '{$data['name']}' (admin_name: '{$tenant->admin_name}')");
            }
            if (isset($data['email'])) {
                $this->line("    ℹ️  email: '{$data['email']}' (admin_email: '{$tenant->admin_email}')");
            }
            if (isset($data['temp_password'])) {
                $this->line('    🔑 temp_password: '.substr($data['temp_password'], 0, 8).'...');
            }

            if (empty($updates)) {
                $this->info('  ✅ No updates needed');
                $skipped++;

                continue;
            }

            if (! $dryRun) {
                // Perform the actual update
                DB::table('tenants')->where('id', $tenant->id)->update($updates);
                $this->info('  ✅ Updated '.count($updates).' column(s)');
                $updated++;
            } else {
                $this->info('  🔍 Would update '.count($updates).' column(s)');
            }
        }

        $this->newLine();
        if ($dryRun) {
            $this->info('🔍 DRY RUN SUMMARY:');
            $this->info("  - Tenants that would be updated: {$updated}");
            $this->info("  - Tenants skipped: {$skipped}");
            $this->info("\n💡 Run without --dry-run to apply changes");
        } else {
            $this->info('✅ SYNC COMPLETE:');
            $this->info("  - Tenants updated: {$updated}");
            $this->info("  - Tenants skipped: {$skipped}");
        }

        return 0;
    }
}
