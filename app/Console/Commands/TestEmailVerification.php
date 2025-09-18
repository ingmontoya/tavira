<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-verification {email : Email address to test verification sending}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test email verification sending for a specific user';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');

        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email '{$email}' not found");

            return Command::FAILURE;
        }

        $this->info("Testing email verification for: {$email}");
        $this->newLine();

        // Check mail configuration
        $this->checkMailConfiguration();
        $this->newLine();

        // Check if user needs verification
        if ($user->hasVerifiedEmail()) {
            $this->warn('⚠️  User email is already verified');
            $response = $this->confirm('Do you want to test sending anyway?');
            if (! $response) {
                return Command::SUCCESS;
            }
        }

        // Test sending verification email
        try {
            $this->info('Attempting to send verification email...');

            $user->sendEmailVerificationNotification();

            $this->info('✅ Verification email sent successfully!');
            $this->line("Check the user's inbox and spam folder.");

        } catch (\Exception $e) {
            $this->error('❌ Failed to send verification email:');
            $this->error($e->getMessage());

            if (app()->environment('local')) {
                $this->newLine();
                $this->warn('Full error trace:');
                $this->line($e->getTraceAsString());
            }
        }

        return Command::SUCCESS;
    }

    private function checkMailConfiguration()
    {
        $this->info('Mail Configuration Check:');

        $config = [
            'MAIL_MAILER' => config('mail.default'),
            'MAIL_HOST' => config('mail.mailers.smtp.host'),
            'MAIL_PORT' => config('mail.mailers.smtp.port'),
            'MAIL_FROM_ADDRESS' => config('mail.from.address'),
            'MAIL_FROM_NAME' => config('mail.from.name'),
        ];

        foreach ($config as $key => $value) {
            if (empty($value)) {
                $this->error("❌ {$key}: Not set");
            } else {
                $this->line("✅ {$key}: {$value}");
            }
        }

        // Test if mail is properly configured
        try {
            $mailer = Mail::mailer();
            $this->info('✅ Mail driver loaded successfully');
        } catch (\Exception $e) {
            $this->error('❌ Mail driver error: '.$e->getMessage());
        }
    }
}
