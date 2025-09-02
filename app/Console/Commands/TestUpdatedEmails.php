<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Mail\WelcomeEmail;
use App\Notifications\CustomVerifyEmailNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestUpdatedEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:test-updated {email : Email address to test} {--type=both : Type of email to test (verification, welcome, both)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the updated email templates (verification and welcome)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $type = $this->option('type');

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found");
            return Command::FAILURE;
        }

        $this->info("Testing updated email templates for: {$email}");
        $this->newLine();

        try {
            if ($type === 'verification' || $type === 'both') {
                $this->info("📧 Testing verification email...");
                $user->notify(new CustomVerifyEmailNotification);
                $this->info("✅ Verification email sent successfully!");
                $this->newLine();
            }

            if ($type === 'welcome' || $type === 'both') {
                $this->info("📧 Testing welcome email...");
                Mail::to($user)->send(new WelcomeEmail($user));
                $this->info("✅ Welcome email sent successfully!");
                $this->newLine();
            }

            $this->info("🎉 All emails sent! Check the inbox for:");
            $this->line("• Modern design with Tavira branding");
            $this->line("• Logo placeholder (add real logo to public/img/tavira_logo_blanco.svg)");
            $this->line("• Responsive layout for mobile devices");
            $this->line("• Clear call-to-action buttons");
            $this->newLine();

            $this->comment("Note: If you see duplicate emails, the cache system should prevent them on subsequent sends.");
        } catch (\Exception $e) {
            $this->error("❌ Error sending emails: " . $e->getMessage());

            if (app()->environment('local')) {
                $this->newLine();
                $this->warn("Full error trace:");
                $this->line($e->getTraceAsString());
            }

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
