<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class CreateInvitation extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invite:create {email : Email to invite} {--role=residente : Role to assign} {--expires=24 : Hours until expiration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an invitation for user registration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $email = $this->argument('email');
        $role = $this->option('role');
        $expiresHours = (int) $this->option('expires');
        
        // Validate role
        $validRoles = ['admin', 'admin_conjunto', 'residente', 'portero', 'consejo'];
        if (!in_array($role, $validRoles)) {
            $this->error("Invalid role. Valid roles: " . implode(', ', $validRoles));
            return Command::FAILURE;
        }

        // Check if invitation already exists for this email
        $existingInvitation = Invitation::where('email', $email)
            ->where('expires_at', '>', now())
            ->first();

        if ($existingInvitation) {
            $this->warn("Active invitation already exists for {$email}");
            $this->info("Token: {$existingInvitation->token}");
            $this->info("Registration URL: " . route('register', ['token' => $existingInvitation->token]));
            return Command::SUCCESS;
        }

        // Create new invitation
        $invitation = Invitation::create([
            'email' => $email,
            'role' => $role,
            'token' => Str::random(64),
            'expires_at' => now()->addHours($expiresHours),
            'invited_by' => 1, // Assume first admin user
        ]);

        $registrationUrl = route('register', ['token' => $invitation->token]);

        $this->info("✅ Invitation created successfully!");
        $this->newLine();
        $this->line("📧 Email: {$email}");
        $this->line("👤 Role: {$role}");
        $this->line("⏰ Expires: " . $invitation->expires_at->format('Y-m-d H:i:s'));
        $this->line("🔑 Token: {$invitation->token}");
        $this->newLine();
        $this->info("🔗 Registration URL:");
        $this->line($registrationUrl);
        $this->newLine();
        $this->comment("The user can now register using this URL.");

        return Command::SUCCESS;
    }
}