<?php

namespace App\Listeners;

use App\Mail\WelcomeEmail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmail
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Verified $event): void
    {
        // Prevent duplicate emails by checking if welcome email was already sent
        $cacheKey = 'welcome_email_sent_'.$event->user->id;

        if (cache()->has($cacheKey)) {
            return; // Email already sent recently
        }

        // Send the welcome email
        Mail::to($event->user)->send(new WelcomeEmail($event->user));

        // Cache for 24 hours to prevent duplicates
        cache()->put($cacheKey, true, now()->addHours(24));
    }
}
