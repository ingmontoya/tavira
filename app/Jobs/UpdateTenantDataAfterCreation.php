<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class UpdateTenantDataAfterCreation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $tenantId;
    protected array $customData;

    /**
     * Create a new job instance.
     */
    public function __construct(string $tenantId, array $customData)
    {
        $this->tenantId = $tenantId;
        $this->customData = $customData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $tenant = Tenant::find($this->tenantId);
            
            if (!$tenant) {
                \Log::warning('Tenant not found for data update', ['tenant_id' => $this->tenantId]);
                return;
            }

            // Get current data from the tenant (may have been modified by pipeline)
            $currentData = is_array($tenant->data) ? $tenant->data : (json_decode($tenant->data ?? '{}', true) ?: []);
            
            // Merge current data with our custom data (our data takes precedence)
            $finalData = array_merge($currentData, $this->customData);

            // Use direct SQL update to ensure data persistence
            DB::table('tenants')
                ->where('id', $this->tenantId)
                ->update(['data' => json_encode($finalData)]);

            \Log::info('Tenant data updated successfully after creation', [
                'tenant_id' => $this->tenantId,
                'final_data_keys' => array_keys($finalData)
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to update tenant data after creation', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to trigger failed job handling
            throw $e;
        }
    }
}