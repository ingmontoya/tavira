<?php

namespace App\Console\Commands;

use App\Mail\ProviderApproved;
use App\Models\Central\Provider;
use App\Models\ProviderRegistration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestProviderApprovedEmail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:provider-email {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test provider approval email';

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

        // Create a temporary provider for testing
        $provider = Provider::create([
            'name' => '[TEST] ' . $registration->company_name,
            'category' => $registration->service_type,
            'email' => $registration->email,
            'contact_name' => $registration->contact_name,
            'phone' => $registration->phone,
            'contact_phone' => $registration->phone,
            'contact_email' => $registration->email,
            'is_active' => true,
        ]);

        $this->info("Sending test email to: {$email}");
        $this->info("Using data from registration: {$registration->company_name}");

        try {
            Mail::to($email)->queue(new ProviderApproved($registration, $provider));
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
