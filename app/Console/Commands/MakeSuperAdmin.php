<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class MakeSuperAdmin extends Command
{
    protected $signature = 'user:make-superadmin {email} {name?} {password?}';
    protected $description = 'Create a new user and make them a superadmin';

    public function handle()
    {
        $email = $this->argument('email');
        $name = $this->argument('name') ?: $this->ask('Enter the user name');
        $password = $this->argument('password') ?: $this->secret('Enter the user password');
        
        $user = User::where('email', $email)->first();
        
        if ($user) {
            $this->info("User with email {$email} already exists. Assigning superadmin role...");
        } else {
            $this->info("Creating new user with email {$email}...");
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => bcrypt($password),
                'email_verified_at' => now(),
            ]);
        }
        
        // Ensure superadmin role exists
        $role = Role::firstOrCreate(['name' => 'superadmin']);
        
        $user->assignRole('superadmin');
        
        $this->info("User {$user->name} ({$email}) is now a superadmin.");
        return 0;
    }
}