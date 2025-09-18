<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class ForceEmailVerification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:force-verify {email : Email address to verify}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Force verify a user email address';

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

        if ($user->hasVerifiedEmail()) {
            $this->info("User '{$email}' is already verified");

            return Command::SUCCESS;
        }

        // Force verification
        $user->markEmailAsVerified();

        $this->info("✅ Email verification forced for user: {$email}");
        $this->line('User can now access the system normally.');

        return Command::SUCCESS;
    }
}
