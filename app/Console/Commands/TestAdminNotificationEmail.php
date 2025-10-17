<?php

namespace App\Console\Commands;

use App\Mail\NewProviderRegistration;
use App\Models\ProviderRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestAdminNotificationEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:admin-notification {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test admin notification email for new provider registration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        // Get first pending registration for test data
        $registration = ProviderRegistration::pending()->first();

        if (!$registration) {
            $this->error('No pending registrations found to use as test data.');
            return 1;
        }

        $this->info("Sending test admin notification to: {$email}");
        $this->info("Using data from registration: {$registration->company_name}");

        try {
            Mail::to($email)->queue(new NewProviderRegistration($registration));
            $this->info('✓ Email queued successfully!');
            $this->info('⏳ Run "php artisan queue:work" to process the email.');

            // Check if job was added to queue
            $jobsCount = \DB::table('jobs')->count();
            $this->info("📊 Jobs in queue: {$jobsCount}");

            return 0;
        } catch (\Exception $e) {
            $this->error('✗ Failed to queue email: ' . $e->getMessage());
            return 1;
        }
    }
}
