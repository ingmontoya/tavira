<?php

namespace App\Listeners;

use App\Events\CorrespondenceReceived;
use App\Models\Resident;
use App\Models\User;
use App\Notifications\CorrespondenceReceivedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SendCorrespondenceNotification
{
    /**
     * Handle the event.
     */
    public function handle(CorrespondenceReceived $event): void
    {
        $correspondence = $event->correspondence;
        $apartment = $correspondence->apartment;

        // Get all active residents with email notifications enabled for this apartment
        $residents = Resident::where('apartment_id', $apartment->id)
            ->where('status', 'Active')
            ->where('email_notifications', true)
            ->whereNotNull('email')
            ->get();

        // Notify residents via email if they have email_notifications enabled
        foreach ($residents as $resident) {
            try {
                // Use Notification facade to send email to resident
                Notification::route('mail', [
                    $resident->email => $resident->full_name,
                ])->notify(new CorrespondenceReceivedNotification($correspondence, $resident->full_name));

                Log::info('Correspondence notification sent to resident', [
                    'resident_id' => $resident->id,
                    'resident_email' => $resident->email,
                    'correspondence_id' => $correspondence->id,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send correspondence notification to resident', [
                    'resident_id' => $resident->id,
                    'resident_email' => $resident->email,
                    'correspondence_id' => $correspondence->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
