<?php

namespace App\Jobs;

use App\Models\MaintenanceRequest;
use App\Models\User;
use App\Notifications\UpcomingMaintenanceNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class NotifyUpcomingMaintenanceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Starting NotifyUpcomingMaintenanceJob');

        // Get all maintenance requests that need notification
        $maintenanceRequests = MaintenanceRequest::needsNotification()
            ->with(['maintenanceCategory', 'conjuntoConfig'])
            ->get();

        Log::info("Found {$maintenanceRequests->count()} maintenance requests that need notification");

        foreach ($maintenanceRequests as $maintenanceRequest) {
            try {
                // Get users who should receive notifications (admins, managers, etc.)
                $users = User::whereHas('roles', function ($query) {
                    $query->whereIn('name', ['admin', 'manager', 'maintenance_manager']);
                })->get();

                // Also notify apartment residents if the maintenance is apartment-specific
                if ($maintenanceRequest->apartment_id) {
                    $apartmentUsers = User::where('apartment_id', $maintenanceRequest->apartment_id)->get();
                    $users = $users->merge($apartmentUsers)->unique('id');
                }

                // Send notification to each user
                foreach ($users as $user) {
                    $user->notify(new UpcomingMaintenanceNotification($maintenanceRequest));
                }

                // Mark as notified
                $maintenanceRequest->markAsNotified();

                Log::info("Sent notifications for maintenance request #{$maintenanceRequest->id} to {$users->count()} users");
            } catch (\Exception $e) {
                Log::error("Failed to send notification for maintenance request #{$maintenanceRequest->id}: {$e->getMessage()}");
            }
        }

        Log::info('Finished NotifyUpcomingMaintenanceJob');
    }
}
