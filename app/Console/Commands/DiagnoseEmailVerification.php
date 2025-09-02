<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\URL;

class DiagnoseEmailVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:diagnose-verification {--user-email= : Email of specific user to diagnose}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Diagnose email verification issues';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userEmail = $this->option('user-email');
        
        $this->info('=== Email Verification Diagnostics ===');
        $this->newLine();

        // Basic configuration check
        $this->checkConfiguration();
        $this->newLine();

        // If specific user provided, diagnose that user
        if ($userEmail) {
            $this->diagnoseUser($userEmail);
        } else {
            // Show all unverified users
            $this->showUnverifiedUsers();
        }

        return Command::SUCCESS;
    }

    private function checkConfiguration()
    {
        $this->info('Configuration Check:');
        
        $appUrl = config('app.url');
        $appEnv = config('app.env');
        $mailFrom = config('mail.from.address');
        
        $this->line("APP_URL: {$appUrl}");
        $this->line("APP_ENV: {$appEnv}");
        $this->line("MAIL_FROM: {$mailFrom}");
        
        // Check if APP_URL matches current request
        if (app()->runningInConsole()) {
            $this->comment('Running in console - cannot compare with request URL');
        }

        // Check mail configuration
        if (empty($mailFrom)) {
            $this->error('MAIL_FROM is not configured properly');
        } else {
            $this->info('Mail configuration looks good');
        }
    }

    private function diagnoseUser(string $email)
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            $this->error("User with email '{$email}' not found");
            return;
        }

        $this->info("Diagnosing user: {$user->email} (ID: {$user->id})");
        $this->newLine();

        // Check verification status
        if ($user->hasVerifiedEmail()) {
            $this->info('✓ Email is already verified');
        } else {
            $this->warn('✗ Email is NOT verified');
            $this->line("Email verified at: " . ($user->email_verified_at ?: 'Never'));
        }

        // Check roles
        $roles = $user->getRoleNames();
        if ($roles->isEmpty()) {
            $this->error('✗ User has NO roles assigned');
        } else {
            $this->info('✓ User roles: ' . $roles->join(', '));
        }

        // Generate a sample verification URL
        try {
            $verificationUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                [
                    'id' => $user->getKey(),
                    'hash' => sha1($user->getEmailForVerification()),
                ]
            );
            
            $this->info('Sample verification URL:');
            $this->line($verificationUrl);
        } catch (\Exception $e) {
            $this->error('Error generating verification URL: ' . $e->getMessage());
        }
    }

    private function showUnverifiedUsers()
    {
        $unverifiedUsers = User::whereNull('email_verified_at')->get();
        
        if ($unverifiedUsers->isEmpty()) {
            $this->info('No unverified users found');
            return;
        }

        $this->warn("Found {$unverifiedUsers->count()} unverified users:");
        $this->newLine();

        $headers = ['ID', 'Email', 'Created', 'Roles', 'Tenant ID'];
        $rows = [];

        foreach ($unverifiedUsers as $user) {
            $roles = $user->getRoleNames();
            $rows[] = [
                $user->id,
                $user->email,
                $user->created_at->format('Y-m-d H:i:s'),
                $roles->isEmpty() ? '❌ NO ROLES' : $roles->join(', '),
                $user->tenant_id ?? 'null'
            ];
        }

        $this->table($headers, $rows);
        
        $this->newLine();
        $this->comment('To diagnose a specific user, use: php artisan email:diagnose-verification --user-email=user@example.com');
    }
}