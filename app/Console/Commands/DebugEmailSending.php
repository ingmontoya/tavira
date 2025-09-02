<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;

class DebugEmailSending extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'debug:email-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug email sending events and listeners';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Email Events Debug ===');
        $this->newLine();

        // Check listeners for Registered event
        $this->info('Listeners for Registered event:');
        $registeredListeners = Event::getListeners(Registered::class);
        if (empty($registeredListeners)) {
            $this->warn('No explicit listeners found for Registered event');
            $this->comment('Laravel automatically sends verification email for MustVerifyEmail users');
        } else {
            foreach ($registeredListeners as $listener) {
                $this->line("- " . (is_string($listener) ? $listener : get_class($listener)));
            }
        }
        $this->newLine();

        // Check listeners for Verified event
        $this->info('Listeners for Verified event:');
        $verifiedListeners = Event::getListeners(Verified::class);
        if (empty($verifiedListeners)) {
            $this->warn('No listeners found for Verified event');
        } else {
            foreach ($verifiedListeners as $listener) {
                $this->line("- " . (is_string($listener) ? $listener : get_class($listener)));
            }
        }
        $this->newLine();

        // Check if User implements MustVerifyEmail
        $user = new \App\Models\User();
        $implementsVerify = $user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail;
        $this->info("User model implements MustVerifyEmail: " . ($implementsVerify ? '✅ Yes' : '❌ No'));
        
        if ($implementsVerify) {
            $this->comment('This means Laravel automatically sends verification emails on registration');
        }
        $this->newLine();

        // Show potential causes of duplicate emails
        $this->warn('Potential causes of duplicate verification emails:');
        $this->line('1. Multiple calls to event(new Registered($user)) in controllers');
        $this->line('2. Custom listeners duplicating Laravel\'s automatic behavior');
        $this->line('3. Queue workers processing the same job multiple times');
        $this->line('4. Multiple event listeners for the same event');

        return Command::SUCCESS;
    }
}