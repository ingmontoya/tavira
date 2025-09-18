<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Events\DatabaseSeeded;

class UpdateTenantDataAfterSeeding
{
    public function handle(DatabaseSeeded $event): void
    {
        $tenant = $event->tenant;

        // Check if we have pending data to update (using the new pending_updates field)
        $pendingData = $tenant->pending_updates;

        if (! $pendingData) {
            return; // No pending data to update
        }

        Log::info('Updating tenant data after pipeline completion', [
            'tenant_id' => $tenant->id,
            'pending_data' => $pendingData,
        ]);

        try {
            // Extract the data to update
            $tenantData = $pendingData['tenant_data'] ?? [];
            $updateFields = $pendingData['update_fields'] ?? [];

            // Merge tenant data with existing data (data field is managed by stancl/tenancy)
            $newData = array_merge($tenant->data ?? [], $tenantData);

            // Update the tenant using Eloquent for proper JSON casting
            $tenant->update(array_merge([
                'data' => $newData,
                'pending_updates' => null, // Clear pending updates after processing
            ], $updateFields));

            // Create tenant admin user in the tenant database
            $adminUserId = null;
            if (isset($tenantData['temp_password'])) {
                $tenant->run(function () use (&$adminUserId, $tenantData) {
                    // Create the admin user in the tenant database
                    $tenantUser = \App\Models\User::create([
                        'name' => $tenantData['name'],
                        'email' => $tenantData['email'],
                        'password' => \Illuminate\Support\Facades\Hash::make($tenantData['temp_password']),
                        'email_verified_at' => now(),
                        'requires_subscription' => false, // Tenant users don't need subscription
                    ]);

                    // Assign admin role
                    if (class_exists(\Spatie\Permission\Models\Role::class)) {
                        $adminRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
                        $tenantUser->assignRole($adminRole);
                    }

                    $adminUserId = $tenantUser->id;
                });

                // Update tenant with admin user ID
                if ($adminUserId) {
                    $tenant->update(['admin_user_id' => $adminUserId]);
                }

                // Send credentials email - find the user who created the tenant
                try {
                    $creatorUserId = $tenantData['created_by'] ?? null;
                    if ($creatorUserId) {
                        $creatorUser = \App\Models\User::find($creatorUserId);
                        if ($creatorUser) {
                            $tenantDomain = $tenant->domains->first()->domain ?? null;
                            if ($tenantDomain) {
                                $creatorUser->notify(new \App\Notifications\TenantCredentialsCreated(
                                    $tenant,
                                    $tenantData['temp_password'],
                                    $tenantDomain
                                ));
                            }
                        }
                    }
                } catch (\Exception $emailError) {
                    Log::warning('Failed to send tenant credentials email', [
                        'tenant_id' => $tenant->id,
                        'error' => $emailError->getMessage(),
                    ]);
                }
            }

            Log::info('Tenant data updated successfully after pipeline', [
                'tenant_id' => $tenant->id,
                'updated_data' => $newData,
                'updated_fields' => $updateFields,
                'admin_user_id' => $adminUserId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update tenant data after pipeline', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
